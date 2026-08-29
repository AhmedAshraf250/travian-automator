<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('travian:runtime {--host= : Host used by the local Laravel server} {--port= : Port used by the local Laravel server} {--no-server : Run only queue and scheduler workers} {--open : Open the dashboard in the browser} {--verbose-workers : Print child process output to this console}')]
#[Description('Run and supervise the local Travian web server, queue worker, and scheduler.')]
class RunTravianRuntimeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $runtimeLock = $this->acquireRuntimeLock();

        if ($runtimeLock === null) {
            $this->warn('Travian runtime is already running. Close the existing START-TRAVIAN window before starting another one.');

            return self::FAILURE;
        }

        $host = (string) ($this->option('host') ?: config('travian.runtime.host', '127.0.0.1'));
        $port = (int) ($this->option('port') ?: config('travian.runtime.port', 8000));
        $processes = $this->startRuntimeProcesses($host, $port);

        $this->info('Travian runtime supervisor started.');

        if (! (bool) $this->option('no-server')) {
            $url = "http://{$host}:{$port}";

            $this->line("Dashboard: {$url}");

            if ((bool) $this->option('open')) {
                $this->openDashboard($url);
            }
        }

        try {
            while (true) {
                foreach ($processes as $name => $process) {
                    $this->flushProcessOutput($name, $process);

                    if (! $process->isRunning()) {
                        if ($name === 'cleanup') {
                            unset($processes[$name]);

                            continue;
                        }

                        $this->warn("{$name} stopped; restarting it.");
                        $processes[$name] = $this->startProcess($name, $this->runtimeCommand($name, $host, $port));

                        continue;
                    }

                    $this->touchRuntimeHeartbeatFor($name);
                }

                sleep(max(1, (int) config('travian.runtime.supervisor_poll_seconds', 2)));
            }
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(5);
                }
            }

            flock($runtimeLock, LOCK_UN);
            fclose($runtimeLock);
        }
    }

    /**
     * @return array<string, Process>
     */
    protected function startRuntimeProcesses(string $host, int $port): array
    {
        $names = (bool) $this->option('no-server')
            ? ['queue', 'scheduler', 'cleanup']
            : ['server', 'queue', 'scheduler', 'cleanup'];
        $processes = [];

        foreach ($names as $name) {
            $processes[$name] = $this->startProcess($name, $this->runtimeCommand($name, $host, $port));
        }

        return $processes;
    }

    /**
     * Keep exactly one local runtime supervisor alive per project checkout.
     *
     * @return resource|null
     */
    protected function acquireRuntimeLock(): mixed
    {
        $lockPath = storage_path('framework/travian-runtime.lock');
        $lockDirectory = dirname($lockPath);

        if (! is_dir($lockDirectory)) {
            mkdir($lockDirectory, 0775, true);
        }

        $handle = fopen($lockPath, 'c');

        if ($handle === false) {
            return null;
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        ftruncate($handle, 0);
        fwrite($handle, (string) getmypid());

        return $handle;
    }

    /**
     * @return list<string>
     */
    protected function runtimeCommand(string $name, string $host, int $port): array
    {
        $timeout = max(30, (int) config('travian.automation.job_timeout_seconds', 90));
        $sleep = max(1, (int) config('travian.runtime.queue_sleep_seconds', 3));
        $memory = max(128, (int) config('travian.runtime.queue_memory_mb', 256));
        $maxTime = max(300, (int) config('travian.runtime.queue_max_time_seconds', 3600));

        return match ($name) {
            'server' => [PHP_BINARY, 'artisan', 'serve', "--host={$host}", "--port={$port}"],
            'queue' => [PHP_BINARY, 'artisan', 'queue:work', "--sleep={$sleep}", '--tries=1', "--timeout={$timeout}", "--memory={$memory}", "--max-time={$maxTime}"],
            'scheduler' => [PHP_BINARY, 'artisan', 'schedule:work'],
            'cleanup' => [PHP_BINARY, 'artisan', 'travian:cleanup-runtime'],
            default => throw new \InvalidArgumentException("Unknown runtime process [{$name}]."),
        };
    }

    /**
     * @param  list<string>  $command
     */
    protected function startProcess(string $name, array $command): Process
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);

        if (! (bool) $this->option('verbose-workers')) {
            $process->disableOutput();
        }

        $process->start();

        $this->line("Started {$name}: ".implode(' ', $command));

        return $process;
    }

    protected function flushProcessOutput(string $name, Process $process): void
    {
        if ($process->isOutputDisabled()) {
            return;
        }

        $output = trim($process->getIncrementalOutput());
        $errorOutput = trim($process->getIncrementalErrorOutput());

        if ($output !== '') {
            $this->line("[{$name}] {$output}");
        }

        if ($errorOutput !== '') {
            $this->warn("[{$name}] {$errorOutput}");
        }
    }

    protected function touchRuntimeHeartbeatFor(string $name): void
    {
        if (! in_array($name, ['queue', 'scheduler'], true)) {
            return;
        }

        static $lastHeartbeatAt = [];

        $component = $name === 'queue' ? 'queue_worker' : 'scheduler';
        $interval = max(5, (int) config(
            'travian.runtime.heartbeat_interval_seconds',
            config('travian.runtime.queue_heartbeat_interval_seconds', 30),
        ));
        $now = time();

        if (($now - ($lastHeartbeatAt[$component] ?? 0)) < $interval) {
            return;
        }

        $lastHeartbeatAt[$component] = $now;
        SystemSetting::markRuntimeHeartbeat($component);
    }

    protected function openDashboard(string $url): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => ['cmd', '/c', 'start', '', $url],
            'Darwin' => ['open', $url],
            default => ['xdg-open', $url],
        };

        try {
            (new Process($command, base_path()))->start();
        } catch (\Throwable $throwable) {
            $this->warn('Could not open the browser automatically: '.$throwable->getMessage());
        }
    }
}
