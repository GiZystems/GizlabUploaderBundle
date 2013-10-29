<?php
// TransferCommand.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 9/18/13
 * Time: 11:01 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\UploaderBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransferCommand extends UploaderCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('gizlab:uploader:transfer')
            ->setDescription('Transfer data from database to file system, or from file system to database.')
            ->addArgument('destination', InputArgument::REQUIRED, 'Allowed values: file, database')
            ->addOption('clear-source-data', 'c', InputArgument::OPTIONAL, 'Clear all data from source', false);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $destination = strtolower($input->getArgument('destination'));
        if (!in_array($destination, array('file', 'database'))){
            throw new \Exception('Wrong value for argument "destination". Allowed values: file, database');
        }

        $clearSource = $input->getOption('clear-source-data');

        if ($clearSource){

        }
        if ($destination == 'file'){
            $this->getUploaderService()->transferToFileSystem($output, $clearSource);
        } else {
            $this->getUploaderService()->transferToDatabase($output, $clearSource);
        }
    }
}