<?php

namespace PHPackage;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    public function configure()
    {
        $this->setName('new')
             ->setDescription('Create a new package boilerplate')
             ->addArgument('name', InputArgument::REQUIRED)
             ->addOption('src', null, InputOption::VALUE_OPTIONAL, 'Boilerplate from the selected source', 'self');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd();
        $src = $input->getOption('src');
        $path = "{$directory}/{$input->getArgument('name')}";

        $this->assertFolderDoesNotExist($path);

        $this->install($src, $directory)
             ->rename($this->folder, $path);
    }

    private function assertFolderDoesNotExist($path)
    {
        if (is_dir($path)) {
            throw new RuntimeException('Folder already exists!');
        }
    }

    private function install($src, $directory)
    {
        if ($src == 'skeleton') {
            $this->download($zipFile = $this->makeFileName())
            ->extract($zipFile, $directory)
            ->removeZipFile($zipFile);

            $this->folder = "{$directory}/skeleton-master";

            return $this;
        }

        $this->extract($zipFile = __DIR__ . '/template.zip', $directory);

        $this->folder = "{$directory}/template";

        return $this;
    }

    private function download($zipFile)
    {
        $response = (new Client)->get('https://github.com/thephpleague/skeleton/archive/master.zip')->getBody();

        file_put_contents($zipFile, $response);

        return $this;
    }

    private function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();

        return $this;
    }

    private function makeFileName()
    {
        return getcwd() . '/skeleton_' . md5(time() . uniqid()) . '.zip';
    }

    private function removeZipFile($zipFile)
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);
    }

    private function rename($folder, $path)
    {
        rename($this->folder, $path);
    }
}
