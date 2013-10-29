<?php
namespace Gizlab\Bundle\UploaderBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service for uploading any files
 *
 * @author juriem
 *
 */
class UploaderService
{

	/**
	 *
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $entityManager;

	/**
	 * Cache directory for store temporary files
	 * @var string
	 */
	private $cacheDir;

	/**
	 * Data directory for storing files
	 * @var string
	 */
	private $dataDirectory;

	/**
	 * Extension configuration
	 *
	 * @var array
	 */
	private $config;


    /**
     * Constructor
     *
     * @param \Doctrine\Orm\EntityManager $entityManager
     * @param string $cacheDir
     * @param string $rootDir
     * @param array $config
     */
    public function __construct(\Doctrine\Orm\EntityManager $entityManager, $cacheDir, $rootDir, $config)
	{
		/*
		 * Entity manager
		 */
		$this->entityManager = $entityManager;

		/*
		 * Processing cache directory
		 */
		$this->cacheDir = $cacheDir . '/uploader';
		if (!file_exists($this->cacheDir)) {
			mkdir($this->cacheDir, 0777, true);
		}

		/*
		 * Processing data directory
		 */
		$this->dataDirectory = $rootDir.'/data/uploader';
		if (!file_exists($this->dataDirectory)) {
			mkdir($this->dataDirectory, 0777, true);
		}

		/*
		 * Processing configuration
		 */
		$this->config = $config;
	}

	/**
	 * Upload file
	 *
	 * @param int $id - Id of file
	 * @return boolean|\Andevis\Bundle\UploaderBundle\Entity\File
	 */
	public function upload($id = null)
	{
		if (isset($_GET['qqfile'])) {
			// Processing XHR
			$originalFileName = $_GET['qqfile'];
			$input = fopen("php://input", "r");
			$temp = tmpfile();
			$realSize = stream_copy_to_stream($input, $temp);

			// Check file size
			if ($realSize != $this->getSize()){
				return false;
			}
		} elseif(isset($_FILES['qqfile'])) {
			// Processing Upload form
			$originalFileName = $_FILES['qqfile']['name'];

		} else {
			return false;
		}

		/*
		 * Processing file entity
		 */
		$file = $this->entityManager->getRepository('AndevisUploaderBundle:File')->findOneById($id);
		if ($file === null) {
			$file = new \Andevis\Bundle\UploaderBundle\Entity\File();
		}

		if ($this->config['store_mode'] == 'database' ) {

			$tempFileName = tempnam($this->cacheDir, 'tmp_');
		} elseif ($this->config['store_mode'] == 'file' || $this->config['store_mode'] == 'both') {
            /*
             * Store mode file or both
             */
			/*
			 * Checking existing file source
			 */
			if ($file->getSource() === null) {
				$tempFileName = tempnam($this->dataDirectory, 'uploader_');
			} else {
				$tempFileName = $file->getSource();
			}
		}

		if (isset($_GET['qqfile'])) {
			$target = fopen($tempFileName, "w");
			fseek($temp, 0, SEEK_SET);
			stream_copy_to_stream($temp, $target);

			fclose($target);
		} else {

			if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $tempFileName)) {
				return false;
			}
		}



		$file->setUploadedDate(new \DateTime());
		$file->setOriginalName($originalFileName);

		/*
		 * Get mime type
		 */
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$file->setType(finfo_file($finfo, $tempFileName));

		/*
		 * Store information in database 
		 */
		if ($this->config['store_mode'] === 'database') {
			/*
			 * Processing database mode
			 */
			$file->setSource(null);
			$file->setContent(file_get_contents($tempFileName));
		} elseif($this->config['store_mode'] == 'file' || $this->config['store_mode'] == 'both')  {
			/*
			 * Processing file or both mode
			 */
            if ($this->config['store_mode'] == 'both'){
                // For both mode also store data in database
                $file->setContent(file_get_contents($tempFileName));
            } else {
                $file->setContent(null);
            }

			$file->setSource($tempFileName);
		}

		/*
		 * Delete temp file
		 */
		if ($this->config['store_mode'] == 'database') {
			/*
			 * In database store mode need to remove temp file for saving disk space
			 */
			unlink($tempFileName);
		}

		$this->entityManager->persist($file);
		$this->entityManager->flush($file);

		return $file;
	}

	/**
	 * Get file content
	 *
	 * @param int $id
	 */
	public function getFile($id, $returnResponse = false, $fileName = null)
	{
		/* @var $file \Andevis\Bundle\UploaderBundle\Entity\File */
		$file = $this->entityManager->getRepository('AndevisUploaderBundle:File')->findOneById($id);
		if ($file === null) {
			throw new \Exception('Resource with id: '.$id.' not found!');
		}

		if ($this->config['store_mode'] === 'file') {
			if (!file_exists($file->getSource())) {
				throw new \Exception('File \''.$file->getSource().'\' not found!');
			}
			$content = file_get_contents($file->getSource());
		} elseif ($this->config['store_mode'] === 'database') {
			/*
			 * Get stream content
			 */
			$content = stream_get_contents($file->getContent());

		}

		if ($returnResponse) {
			if ($fileName == null) {
				$fileName = $file->getOriginalName();
			}

			return new Response($content, 200, array('Content-Type'=>$file->getType(), 'Content-Disposition' => 'attachment; filename="'.$fileName.'"'));
		} else {
			return $content;
		}
	}

	/**
	 * Link file with entity
	 *
	 * @param object $entity - Entity for link with file
	 * @param string $setterMethodName - name of method for setting value
	 * @param int $id - Id of file
	 */
	public function linkFile($entity, $setterMethodName, $id)
	{
		$file = $this->entityManager->getRepository('AndevisUploaderBundle:File')->findOneById($id);
		if ($file !== null) {
			$entity->$setterMethodName($file);

			return true;
		}

		return false;
	}

	/**
	 * Remove file
	 * @param int $id
	 * @return boolean
	 */
	public function remove($id)
	{
		$file = $this->entityManager->getRepository('AndevisUploaderBundle:File')->findOneById($id);
		if ($file !== null) {

			/*
			 * Try to remove file
			 */

			try {

				if ($this->config['store_mode'] === 'file') {
					$source = $file->getSource();
				}

				$this->entityManager->remove($file);
				$this->entityManager->flush();

				if (isset($source)) {
					unlink($source);
				}

			} catch(\Exception $e) {

				return false;
			}

			return true;
		}

		return false;
	}

    /**
     * Transfer all data to file system
     *
     * @param OutputInterface $output - Output interface for print processing data
     * @param bool $clearContent - Clear content data from database
     */
    public function transferToFileSystem(OutputInterface $output = null, $clearContent = false)
    {

        $output->writeln('Start transfer from database to filesystem');

        $rows = $this->entityManager->getRepository('AndevisUploaderBundle:File')->createQueryBuilder('f')
            ->select('f.id')->where('f.content IS NOT NULL')->getQuery()->getScalarResult();

        $allCount = count($rows);
        $currentIndex = 0;

        $output->writeln(sprintf('Founded %s rows.', $allCount));

        foreach($rows as $fileId){

            /* @var $file \Andevis\Bundle\UploaderBundle\Entity\File */
            $file = $this->entityManager->getRepository('AndevisUploaderBundle:File')->findOneById($fileId);

            /*
             * Generate file name
             */
            $fileName = $file->getSource();
            if (!file_exists($fileName)) {
                // Create new file
                $fileName = tempnam($this->dataDirectory, 'uploader_');
            }

            /*
             * Copy data from stream to file
             */
            $fileHandle = fopen($fileName, 'w');
            stream_copy_to_stream($file->getContent(), $fileHandle);
            fclose($fileHandle);

            $file->setSource($fileName);
            // Clear content from source
            if ($clearContent)
                $file->setContent(null);

            $this->entityManager->persist($file);


            $currentIndex++;
            $output->writeln(sprintf('Processing %s from %s', $currentIndex, $allCount));
        }

        $output->writeln('Update database ...');
        $this->entityManager->flush();
        $output->writeln('Transfer completed.');

    }

    /**
     * @param OutputInterface $output
     * @param bool $clearSourceData - Clear source data
     */
    public function transferToDatabase(OutputInterface $output = null, $clearSourceData = false)
    {
        $output->writeln('<info>Transfer data from file system into database</info>');

        $rows = $this->entityManager->getRepository('AndevisUploaderBundle:File')
            ->createQueryBuilder('f')
            ->select('f.id')->where('f.source IS NOT NULL')
            ->getQuery()->getScalarResult();

        $allCount = count($rows);
        $currentIndex = 0;
        $notFoundedCount = 0;

        $output->writeln(sprintf('<info>Founded %s files.</info> Processing data', $allCount));

        foreach($rows as $fileId){
            /* @var $file \Andevis\Bundle\UploaderBundle\Entity\File */
            $file = $this->entityManager->getRepository('AndevisUploaderBundle:File')->findOneById($fileId);

            $fileName = $file->getSource();
            if (!file_exists($fileName)){
                $notFoundedCount++;
            } else {
                $file->setContent(file_get_contents($fileName));

                if ($clearSourceData){
                    unlink($fileName);
                    $file->setSource(null);
                }
                $this->entityManager->persist($file);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $currentIndex++;
            $output->writeln(sprintf('<info>Processing %s from %s.</info>', $currentIndex, $allCount));

        }

        $output->writeln('Update database ...');
        $this->entityManager->flush();
        $output->writeln(sprintf('Transfer completed. Founded %s wrong links to file', $notFoundedCount));
    }

    /**
     * Cleanup file system
     *
     * @param OutputInterface $output
     */
    public function cleanUpFileSystem(OutputInterface $output)
    {
        $output->writeln('Starting cleaning file system');

        $directoryHandle = opendir($this->dataDirectory);
        $filesForUnlink = array();
        $output->writeln('Checking files ...');
        while($file = readdir($directoryHandle)){
            if (!in_array($file, array('.','..'))){
                $fileName = $this->dataDirectory.'/'.$file;

                $cnt = $this->entityManager->getRepository('AndevisUploaderBundle:File')->createQueryBuilder('f')
                    ->select('COUNT(f) AS cnt')
                    ->where('f.source = :source')->setParameter('source', $fileName)->getQuery()->getSingleScalarResult();

                if ($cnt == 0){
                    $filesForUnlink[] = $fileName;
                }
            }
        }
        closedir($directoryHandle);

        if (count($filesForUnlink) > 0){
            $output->writeln(sprintf('Founded %s files for delete.', count($filesForUnlink)));
            $output->writeln('Processing delete files...');
            foreach($filesForUnlink as $fileName){
                unlink($fileName);
            }

        } else {
            $output->writeln('Files for deleting not found!');
        }

        $output->writeln('Completed.');
    }

	/**
	 * Get content size
	 * @throws Exception
	 * @return number
	 */
	private function getSize()
	{
		if (isset($_SERVER["CONTENT_LENGTH"])){
			return (int)$_SERVER["CONTENT_LENGTH"];
		} else {
			throw new \Exception('Getting content length is not supported.');
		}
	}

}
