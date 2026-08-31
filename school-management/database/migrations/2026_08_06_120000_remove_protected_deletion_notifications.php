<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('activity_log')->whereRaw("JSON_EXTRACT(properties, '$.protected_until') IS NOT NULL")->delete();
    }
    public function down(): void {}
};
