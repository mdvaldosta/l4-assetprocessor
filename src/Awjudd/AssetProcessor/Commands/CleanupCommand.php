<?php namespace Awjudd\AssetProcessor\Commands;

use Awjudd\AssetProcessor\AssetProcessor;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class CleanupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'assetprocessor:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes any clean up any asset files that are no longer needed.';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        $app = app();

        return array(
            array('duration', '-d', InputOption::VALUE_OPTIONAL, 'The length of time (in seconds) that a file needs to be left untouched prior to deleting.'),
        );
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // Grab the duration
        $duration = $this->option('duration');

        // Check if the duration was provided
        if($duration === null)
        {
            // It was, so grab it from the configuration
            $duration = \Config::get('assetprocessor::cache.duration');
        }

        // Grab the minimum acceptable timestamp
        $timestamp = time() - $duration;

        // Get the full list of files
        $files = $this->buildFileList(\AssetProcessor::storageFolder());

        // Cycle through the list checking their creation dates
        foreach($files as $file)
        {
            // Compare the file modification times
            if(filemtime($file) < $timestamp)
            {
                // It passes the acceptable, so remove it
                unlink($file);

                // Check if the directory containing this file is empty
                if(count(scandir(dirname($file))) == 2)
                {
                    // It was empty, so just remove it
                    rmdir(dirname($file));
                }
            }
        }
        
    }

    /**
     * Used internally in order to build a full list of all of the files to build.
     *
     * @param string $folder The folder to scan through
     * @return array An array of all of the files to check.
     */
    private function buildFileList($folder)
    {
        // The list of files to use
        $files = [];

        // Cycle through all of the files in our storage folder removing
        // any files that exceed the cache duration.
        $directory = new \DirectoryIterator($folder);

        foreach ($directory as $file)
        {
            // Check if the file is the local file (i.e. dot)
            if($file->isDot())
            {
                // We are the dot, so just skip
                continue;
            }

            // Check if the file is a directory
            if($file->isDir())
            {
                // Recursively call this function
                $files = array_merge($files, $this->buildFileList($file->getRealPath()));
            }
            else
            {
                // Add in the file
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
}