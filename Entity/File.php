<?php
namespace Gizlab\Bundle\UploaderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Table for storing uploaded content
 *
 * @ORM\Entity
 * @ORM\Table(name="ext_uploader_files")
 * @author juriem
 *
 */
class File
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 * @var int
	 */
	private $id;

	/**
	 * Date of uploading.
	 *
	 * @ORM\Column(type="datetime", name="uploaded_date")
	 * @var \DateTime
	 */
	private $uploadedDate;

	/**
	 * Original uploaded file name
	 * @ORM\Column(type="string", length=255, name="file_name")
	 * @var string
	 */
	private $originalName;

	/**
	 * Mime type of file.
	 * @ORM\Column(type="string", length=255, name="file_type")
	 * @var string
	 */
	private $type;

	/**
	 * File name in app/data/uploader directory. Used used store_mode = file
	 *
	 * @ORM\Column(type="string", length=255, name="file_source", nullable=true)
	 * @var string
	 */
	private $source = null;

	/**
	 * Content of file. Used if use store_mode = database (default mode).
	 * @ORM\Column(type="blob", name="file_content", nullable=true)
	 * @var string
	 */
	private $content = null;

	/**
	 * If true file linked with another entity
	 *
	 * @ORM\Column(type="boolean", name="is_linked")
	 * @var boolean
	 */
	private $linked = false;

	/**
	 * Return image for file
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $format - default - PNG
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getImage($width = null, $height=null, $format="png")
	{
		$image = new \Imagine\Gd\Imagine();
		$image = $image->load(stream_get_contents($this->content));

		if ($width !== null && $height !== null) {
			$imageBox = new \Imagine\Image\Box($width, $height);
			$image = $image->thumbnail($imageBox, \Imagine\Image\ManipulatorInterface::THUMBNAIL_INSET);
		}

		$content = $image->get('png');

		return new \Symfony\Component\HttpFoundation\Response($content, 200, array('content-type'=>'image/png'));
	}

	/*
	 * Auto
	 */

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uploadedDate
     *
     * @param \DateTime $uploadedDate
     * @return File
     */
    public function setUploadedDate($uploadedDate)
    {
        $this->uploadedDate = $uploadedDate;

        return $this;
    }

    /**
     * Get uploadedDate
     *
     * @return \DateTime
     */
    public function getUploadedDate()
    {
        return $this->uploadedDate;
    }

    /**
     * Set originalName
     *
     * @param string $originalName
     * @return File
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get originalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return File
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return File
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return File
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set linked
     *
     * @param boolean $linked
     * @return File
     */
    public function setLinked($linked)
    {
        $this->linked = $linked;

        return $this;
    }

    /**
     * Get linked
     *
     * @return boolean
     */
    public function getLinked()
    {
        return $this->linked;
    }
}