<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\LeaveCountUpdateService;
use Illuminate\Console\Command;

class RecalculateLeaveCountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:recalculate-counts
                            {--employee= : Specific employee ID to recalculate}
                            {--all : Recalculate for all employees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate used leave counts in leave allocation records';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private LeaveCountUpdateService $leaveCountUpdateService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $employeeId = $this->option('employee');
        $all = $this->option('all');

        if (!$employeeId && !$all) {
            $this->error('Please specify either --employee=ID or --all option');
            return self::FAILURE;
        }

        if ($employeeId) {
            return $this->recalculateForEmployee($employeeId);
        }

        if ($all) {
            return $this->recalculateForAllEmployees();
        }

        return self::SUCCESS;
    }

    /**
     * Recalculate leave counts for a specific employee
     */
    private function recalculateForEmployee(int $employeeId): int
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            $this->error("Employee with ID {$employeeId} not found");
            return self::FAILURE;
        }

        $this->info("Recalculating leave counts for employee: {$employee->name}");

        try {
            $this->leaveCountUpdateService->recalculateUsedCountsForEmployee($employeeId);
            $this->info("✅ Leave counts recalculated successfully for employee: {$employee->name}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error recalculating leave counts: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Recalculate leave counts for all employees
     */
    private function recalculateForAllEmployees(): int
    {
        $employees = Employee::all();
        $total = $employees->count();

        if ($total === 0) {
            $this->info('No employees found');
            return self::SUCCESS;
        }

        $this->info("Recalculating leave counts for {$total} employees");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($employees as $employee) {
            try {
                $this->leaveCountUpdateService->recalculateUsedCountsForEmployee($employee->id);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("❌ Error for employee {$employee->name}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully recalculated: {$successCount} employees");

        if ($errorCount > 0) {
            $this->warn("⚠️  Errors encountered: {$errorCount} employees");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
