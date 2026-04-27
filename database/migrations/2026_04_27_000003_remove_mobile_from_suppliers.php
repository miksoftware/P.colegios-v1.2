<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar datos: si phone está vacío pero mobile tiene valor, copiar mobile a phone
        // Si ambos tienen valor, concatenar con " - "
        DB::table('suppliers')->whereNotNull('mobile')->where('mobile', '!=', '')->get()->each(function ($supplier) {
            $phone = trim($supplier->phone ?? '');
            $mobile = trim($supplier->mobile);

            if ($phone === '') {
                $newPhone = $mobile;
            } else {
                $newPhone = $phone . ' - ' . $mobile;
            }

            DB::table('suppliers')->where('id', $supplier->id)->update(['phone' => $newPhone]);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('mobile');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('mobile')->nullable()->after('phone');
        });
    }
};
