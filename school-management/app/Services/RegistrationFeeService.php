<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\SchoolClass;
use App\Models\Student;

class RegistrationFeeService
{
    public function recordAtEnrollment(Student $student, SchoolClass $class, string $payment, ?float $remaining, ?string $dueDate): ?Invoice
    {
        $invoice = $this->ensureStudentInvoice($student, $class);
        if (! $invoice || $payment === 'none') return $invoice;

        $total = (float) $class->registration_fee_amount;
        $remaining = $payment === 'full' ? 0.0 : min($total, max(0.0, (float) $remaining));
        $invoice->update([
            'amount_due' => $total,
            'amount_paid' => $total - $remaining,
            'due_date' => $remaining > 0 ? $dueDate : null,
            'status' => $remaining <= 0 ? 'paid' : 'partial',
        ]);
        return $invoice->fresh();
    }

    public function syncClassInvoices(SchoolClass $class): void
    {
        if ($class->fee_mode !== 'registration_only') return;
        $class->enrollments()->where('status', 'active')->pluck('student_id')->each(
            fn (int $studentId) => $this->ensureStudentInvoice(Student::findOrFail($studentId), $class)
        );
    }

    public function ensureStudentInvoice(Student $student, SchoolClass $class): ?Invoice
    {
        if ($class->fee_mode !== 'registration_only' || (float) $class->registration_fee_amount <= 0) return null;
        $type = InvoiceType::firstOrCreate(['code' => 'registration_fee'], ['name' => 'Droit d’inscription', 'is_salary' => false, 'is_active' => true]);
        $invoice = Invoice::firstOrNew(['student_id' => $student->id, 'school_class_id' => $class->id, 'invoice_type_id' => $type->id]);
        if (! $invoice->exists) {
            $invoice->fill(['reference' => $invoice->reference ?: 'DI-'.$class->id.'-'.$student->id, 'amount_due' => $class->registration_fee_amount, 'due_date' => $invoice->exists ? $invoice->due_date : null, 'status' => $invoice->amount_paid > 0 ? 'partial' : 'unpaid', 'description' => 'Droit d’inscription — '.$class->name])->save();
        }
        return $invoice;
    }
}
