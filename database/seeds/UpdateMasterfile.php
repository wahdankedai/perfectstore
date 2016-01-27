<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class UpdateMasterfile extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(ImportMappingTableSeeder::class);
        $this->call(SubFormTableSeeder::class);
        $this->call(ImportAuditTemplateTableSeeder::class);

        $this->call(UploadSecondaryDisplayTableSeeder::class);
        $this->call(UploadSecondaryDisplayLookupTableSeeder::class);

        $this->call(UploadOsaTableSeeder::class);

        $this->call(FormCategoryTaggingTableSeeder::class);
        $this->call(UploadSosLookupTableSeeder::class);

        $this->call(UploadStoreSosTableSeeder::class);

        Model::reguard();
    }
}
