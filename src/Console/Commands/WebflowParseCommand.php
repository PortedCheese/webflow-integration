<?php

namespace PortedCheese\WebflowIntegration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PortedCheese\WebflowIntegration\Http\Services\FileManager;

class WebflowParseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:webflow-parser {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run webflow parsing';

    protected $fileManager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FileManager $fileManager)
    {
        parent::__construct();

        $this->fileManager = $fileManager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->argument('path');
        $realPath = Storage::disk('public')->path($path);
        $this->fileManager->unzip($realPath);
        Storage::delete($path);

        $this->fileManager->runParser();
    }
}
