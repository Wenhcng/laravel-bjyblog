<?php

namespace App\Console\Commands\Upgrade;

use App\Models\Console;
use Illuminate\Console\Command;
use File;
use Artisan;

class AllVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:allVersions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 运行迁移
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        $consoleModel = new Console();

        // 获取 Console/Commands/Upgrade 目录下的文件并按创建日期排序
        $file = collect(File::files(app_path('Console/Commands/Upgrade')))
            ->transform(function ($v) {
                return [
                    'cTime' => $v->getCTime(),
                    'filename' => basename($v->getFilename(), '.php')
                ];
            })
            ->filter(function ($v) {
                return $v['filename'] === 'AllVersions' ? false : true;
            })
            ->sortBy('cTime')
            ->pluck('filename');

        // 如果还没有执行过升级命令的则执行升级命令
        $console = Console::pluck('name');
        foreach ($file as $k => $v) {
            $name = 'App\Console\Commands\Upgrade\\' . $v;
            if ($console->contains($name)) {
                continue;
            }
            $version = strtolower(str_replace('_', '.', $v));
            $command = 'upgrade:' . $version;
            Artisan::call($command);
            $consoleData = [
                'name' => $name
            ];
            $consoleModel->storeData($consoleData);
            $this->info($version . ' success');
        }
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('clear-compiled');
        Artisan::call('queue:restart');
    }
}
