<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentCompletion;
use App\Models\HomeworkNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function markComplete(User $student, Assignment $assignment): array
    {
        return [];
    }
}
