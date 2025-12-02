<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Console\Command;

class AssignOldComplaints extends Command
{
    protected $signature = 'complaints:assign-old';
    protected $description = 'Assign old complaints to employees based on category';

    public function handle()
    {
        $this->info('Starting to assign old complaints...');
        
        // Get all complaints without assignments
        $complaints = Complaint::whereDoesntHave('assignment')->get();
        
        $assigned = 0;
        $skipped = 0;
        
        foreach ($complaints as $complaint) {
            // Find employee with matching category
            $employee = User::where('role', 'employee')
                ->where('category_id', $complaint->category_id)
                ->first();
            
            if ($employee) {
                // Create assignment
                Assignment::create([
                    'complaint_id' => $complaint->id,
                    'assigned_to' => $employee->id,
                    'assigned_by' => 1, // System/Admin
                    'status' => 'pending',
                    'assigned_at' => now(),
                    'notes' => 'تعيين تلقائي للشكاوى القديمة',
                ]);
                
                // Update complaint status
                $complaint->update(['status' => 'in_review']);
                
                $assigned++;
                $this->info("Assigned complaint #{$complaint->id} to {$employee->name}");
            } else {
                $skipped++;
                $this->warn("No employee found for complaint #{$complaint->id} (category: {$complaint->category_id})");
            }
        }
        
        $this->info("\nDone!");
        $this->info("Assigned: {$assigned}");
        $this->info("Skipped: {$skipped}");
        
        return 0;
    }
}
