<?php

declare(strict_types=1);

/**
 * @file tools/jobs.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class jobs
 * @ingroup tools
 *
 * @brief CLI tool to list, iterate and purge queued jobs on database
 */

namespace PKP\tools;

use APP\facades\Repo;
use Exception;

use Illuminate\Bus\Queueable;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PKP\cliTool\CommandLineTool;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException as CommandInvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Input\StringInput;

use Symfony\Component\Console\Output\StreamOutput;
use Throwable;

define('APP_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
require(APP_ROOT . '/tools/bootstrap.inc.php');

class commandInterface
{
    use InteractsWithIO;

    public function __construct()
    {
        $output = new OutputStyle(
            new StringInput(''),
            new StreamOutput(fopen('php://stdout', 'w'))
        );

        $this->setOutput($output);
    }

    public function errorBlock(array $messages = [], ?string $title = null): void
    {
        $this->getOutput()->block(
            $messages,
            $title,
            'fg=white;bg=red',
            ' ',
            true
        );
    }
}

class commandJobs extends CommandLineTool
{
    protected const AVAILABLE_OPTIONS = [
        'list' => 'List all queued jobs. If you want to paginate results, use the parameters --page= and --perPage=',
        'purge' => 'Purge a specific queued job. If you would like to purge all, pass the parameter --all',
        'test' => 'Add a test job into the default queue',
        'total' => 'Display the queued jobs quantity',
        'help' => 'Display the command usage',
        'usage' => 'Display the command help',
    ];

    /**
     * @var null|string Which option will be call?
     */
    protected $option = null;

    /**
     * @var null|array Parameters and arguments from CLI
     */
    protected $parameterList = null;

    /**
     * CLI interface, this object should extends InteractsWithIO
     */
    protected $commandInterface = null;

    /**
     * Constructor
     */
    public function __construct($argv = [])
    {
        parent::__construct($argv);

        array_shift($argv);

        $this->setParameterList($argv);

        if (!isset($this->getParameterList()[0])) {
            throw new CommandNotFoundException(
                'Option could not be empty! Check the usage method.',
                array_keys(self::AVAILABLE_OPTIONS)
            );
        }

        $this->option = $this->getParameterList()[0];

        $this->setCommandInterface(new commandInterface());
    }

    public function setCommandInterface(commandInterface $commandInterface): self
    {
        $this->commandInterface = $commandInterface;

        return $this;
    }

    public function getCommandInterface(): commandInterface
    {
        return $this->commandInterface;
    }

    /**
     * Save the parameter list passed on CLI
     *
     * @param array $items Array with parameters and arguments passed on CLI
     *
     */
    public function setParameterList(array $items): self
    {
        $parameters = [];

        foreach ($items as $param) {
            if (strpos($param, '=')) {
                [$key, $value] = explode('=', ltrim($param, '-'));
                $parameters[$key] = $value;

                continue;
            }

            $parameters[] = $param;
        }

        $this->parameterList = $parameters;

        return $this;
    }

    /**
     * Get the parameter list passed on CLI
     *
     */
    public function getParameterList(): ?array
    {
        return $this->parameterList;
    }

    protected function getParameterValue(string $parameter, string $default = null): ?string
    {
        if (!isset($this->getParameterList()[$parameter])) {
            return $default;
        }

        return $this->getParameterList()[$parameter];
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        $this->getCommandInterface()->line('<comment>Usage:</comment>');
        $this->getCommandInterface()->line('command [arguments]' . PHP_EOL);
        $this->getCommandInterface()->line('<comment>Available commands for the "jobs" namespace:</comment>');

        $width = $this->getColumnWidth(array_keys(self::AVAILABLE_OPTIONS));

        foreach (self::AVAILABLE_OPTIONS as $commandName => $description) {
            $spacingWidth = $width - Helper::width($commandName);
            $this->getCommandInterface()->line(
                sprintf(
                    '  <info>%s</info>%s%s',
                    $commandName,
                    str_repeat(' ', $spacingWidth),
                    $description
                )
            );
        }
    }

    /**
     * Alias for usage command
     */
    public function help(): void
    {
        $this->usage();
    }

    /**
     * Retrieve the columnWidth based on the commands text size
     */
    protected function getColumnWidth(array $commands): int
    {
        $widths = [];

        foreach ($commands as $command) {
            $widths[] = Helper::width($command);
        }

        return $widths ? max($widths) + 2 : 0;
    }

    /**
     * List all queued jobs
     */
    protected function list(): void
    {
        $perPage = $this->getParameterValue('perPage', '10');
        $page = $this->getParameterValue('page', '1');

        $this->total();

        $data = Repo::job()
            ->setOutputFormat(Repo::job()::OUTPUT_CLI)
            ->perPage((int) $perPage)
            ->setPage((int) $page)
            ->showQueuedJobs();

        $this->getCommandInterface()
            ->table(
                [
                    [
                        new TableCell(
                            'Queued Jobs',
                            [
                                'colspan' => 7,
                                'style' => new TableCellStyle(['align' => 'center'])
                            ]
                        )
                    ],
                    [
                        'ID',
                        'Queue',
                        'Job Display Name',
                        'Attempts',
                        'Reserved At',
                        'Available At',
                        'Created At'
                    ]
                ],
                $data->all(),
            );

        $pagination = [
            'pagination' => [
                0 => $data->currentPage(),
                1 => ($data->currentPage() - 1) > 0 ? $data->currentPage() - 1 : 1,
                2 => $data->currentPage(),
            ],
        ];

        if ($data->hasMorePages()) {
            $pagination['pagination'][2] = $data->currentPage() + 1;
        }

        $this->getCommandInterface()
            ->table(
                [
                    [
                        new TableCell(
                            'Pagination',
                            [
                                'colspan' => 3,
                                'style' => new TableCellStyle(['align' => 'center'])
                            ]
                        )
                    ],
                    [
                        'Current',
                        'Previous',
                        'Next',
                    ]
                ],
                $pagination
            );
    }

    /**
     * Purge queued jobs
     */
    protected function purge(): void
    {
        if (!isset($this->getParameterList()[1])) {
            throw new CommandInvalidArgumentException('You should pass at least a Job ID or `--all` to use this command');
        }

        if ($this->getParameterList()[1] == '--all') {
            $this->purgeAllJobs();

            return;
        }

        $deleted = Repo::job()->delete((int) $this->getParameterList()[1]);

        if (!$deleted) {
            throw new CommandInvalidArgumentException('Invalid job ID');
        }

        $this->getCommandInterface()->getOutput()->success('Job was deleted!');
    }

    /**
     * Purged all queued jobs
     */
    protected function purgeAllJobs(): void
    {
        $deleted = Repo::job()->deleteAll();

        if (!$deleted) {
            throw new LogicException('Was impossible to delete all jobs.');
        }

        $this->getCommandInterface()->getOutput()->success('Deleted all jobs!');
    }

    /**
     * Create a test queued job
     */
    protected function test(): void
    {
        $job = new class() implements ShouldQueue {
            use Dispatchable;
            use InteractsWithQueue;
            use Queueable;
            use SerializesModels;

            public function __construct()
            {
                $this->connection = config('queue.default');
                $this->queue = 'queuedTestJob';
            }

            public function handle(): void
            {
                throw new Exception('cli.test.job');
            }
        };

        dispatch($job);

        $this->getCommandInterface()->getOutput()->success('Dispatched job!');
    }

    /**
     * Display the queued jobs quantity
     */
    protected function total(): void
    {
        $total = Repo::job()
            ->total();

        $this->getCommandInterface()
            ->getOutput()
            ->warning('We have ' . $total . ' queued jobs.');
    }

    /**
     * Parse and execute the command
     */
    public function execute()
    {
        if (!isset(self::AVAILABLE_OPTIONS[$this->option])) {
            throw new CommandNotFoundException(
                sprintf('Option "%s" does not exist.', $this->option),
                array_keys(self::AVAILABLE_OPTIONS)
            );
        }

        $this->{$this->option}();
    }
}

try {
    $tool = new commandJobs($argv ?? []);
    $tool->execute();
} catch (Throwable $e) {
    $output = new commandInterface();

    if ($e instanceof CommandInvalidArgumentException) {
        $output->errorBlock([$e->getMessage()]);

        return;
    }

    if ($e instanceof CommandNotFoundException) {
        $alternatives = $e->getAlternatives();

        $message = 'Did you mean this?';
        if (count($alternatives) > 1) {
            $message = 'Did you mean one of those?';
        }

        $message = $message . PHP_EOL . implode(PHP_EOL, $alternatives);

        $output->errorBlock([$e->getMessage(), $message]);

        return;
    }

    throw $e;
}
