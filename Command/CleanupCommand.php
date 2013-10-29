<?php
// CleanupCommand.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 9/18/13
 * Time: 12:12 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\UploaderBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanupCommand
 * @package Andevis\Bundle\UploaderBundle\Command
 *
 * Clean up file system command
 */
class CleanupCommand extends UploaderCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName('gizlab:uploader:cleanup')
            ->setDescription('Cleanup file system for unlinked files.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getUploaderService()->cleanUpFileSystem($output);
    }
}