<?php

namespace PHPackage;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    protected $fileSystem;

    protected $directory;

    protected $path;

    public function __construct()
    {
        $this->fileSystem = new Filesystem();
        $this->directory = getcwd();

        parent::__construct();
    }

    public function configure()
    {
        $this->setName('new')
             ->setDescription('Create a new package boilerplate')
             ->addArgument('name', InputArgument::REQUIRED)
             ->addOption('src', null, InputOption::VALUE_OPTIONAL, 'Boilerplate from the selected source', 'self')
             ->addOption('unit', null, InputOption::VALUE_OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assertFolderDoesNotExist($input->getArgument('name'))
             ->getTemplateFrom($input->getOption('src'))
             ->createDirectoryStructure($input);

        $output->writeln('<info>Done. Ready for creating an awesome package!</info>');
    }

    private function assertFolderDoesNotExist($folderName)
    {
        $this->path = $this->directory . '/' . $folderName;

        if (is_dir($this->path)) {
            throw new RuntimeException('Folder already exists!');
        }

        return $this;
    }

    private function getTemplateFrom($src)
    {
        if ($src == 'skeleton') {
            $this->zipFile = getcwd() . '/skeleton-master.zip';

            $this->download($this->zipFile);

            return $this;
        }

        $this->zipFile = __DIR__ . '/template.zip';

        return $this;
    }

    private function download($zipFile)
    {
        $response = (new Client)->get('https://github.com/thephpleague/skeleton/archive/master.zip')->getBody();

        file_put_contents($zipFile, $response);

        return $this;
    }

    private function createDirectoryStructure($input)
    {
        $this->extract($this->zipFile)
             ->removeZipFile($this->zipFile);

        rename($this->directory . '/' . pathinfo($this->zipFile, PATHINFO_FILENAME), $this->path);

        $this->handleAdditionalFlags($input);
    }

    private function extract($zipFile)
    {
        $archive = new ZipArchive;
        $archive->open($this->zipFile);
        $archive->extractTo($this->directory);
        $archive->close();

        return $this;
    }

    private function removeZipFile($zipFile)
    {
        if ($zipFile != __DIR__ . '/template.zip') {
            @chmod($zipFile, 0777);
            @unlink($zipFile);
        }
    }

    private function handleAdditionalFlags($input)
    {
        if ($input->hasParameterOption('--unit')) {
            $this->fileSystem->mkdir($this->path . '/tests');
            $this->fileSystem->copy(__DIR__ . '/stubs/phpunit.stub', $this->path . '/phpunit.xml.dist');
            $this->addDevDependency('phpunit/phpunit', '^8.0');
        }
    }

    private function addDevDependency($package, $version)
    {
        $jsonData = json_decode(
            file_get_contents($this->path . '/composer.json'),
            true
        );

        $jsonData['require-dev'][$package] = $version;

        file_put_contents(
            $this->path . '/composer.json',
            json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
