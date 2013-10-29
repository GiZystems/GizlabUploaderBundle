<?php
// UploaderCommand.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 9/18/13
 * Time: 10:49 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\UploaderBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UploaderCommand
 * @package Andevis\Bundle\UploaderBundle\Command
 */
abstract class UploaderCommand extends ContainerAwareCommand
{
    /**
     * Get uploader service
     * @return \Gizlab\Bundle\UploaderBundle\Service\UploaderService
     */
    protected function getUploaderService()
    {
        return $this->getContainer()->get('andevis_uploader');
    }

}