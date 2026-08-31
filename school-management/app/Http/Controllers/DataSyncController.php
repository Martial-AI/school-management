<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DataSyncController extends Controller
{
    public function programsVersion(): JsonResponse
    {
        $version = sha1(implode('|', [DB::table('teaching_programs')->count(), DB::table('teaching_programs')->max('updated_at') ?? '', DB::table('program_lessons')->count(), DB::table('program_lessons')->max('updated_at') ?? '', DB::table('class_schedules')->count(), DB::table('class_schedules')->max('updated_at') ?? '']));
        return response()->json(['version' => $version]);
    }

    public function version(): JsonResponse
    {
        $tables = ['users', 'academic_years', 'school_classes', 'subjects', 'teacher_assignments', 'students', 'guardians', 'enrollments', 'student_documents', 'attendance_sessions', 'attendances', 'biometric_events', 'invoice_types', 'invoices', 'payments', 'employee_payments', 'payment_reminders', 'expenses', 'settings', 'teaching_programs', 'program_lessons', 'class_schedules', 'student_monthly_fees', 'login_histories', 'activity_log', 'notifications', 'login_approvals'];
        $parts = [];
        foreach ($tables as $table) {
            $parts[] = $table.':'.DB::table($table)->count().':'.(DB::table($table)->max('updated_at') ?? DB::table($table)->max('created_at') ?? '');
        }
        return response()->json(['version' => sha1(implode('|', $parts))]);
    }
}
