<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * New database migration.
 *
 * @author Damien MOLINA
 */
return new class extends Migration {

    /**
	 * Name of the table.
	 *
	 * @var string
	 */
	protected $table = 'waf_rules' ;

    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up() {
		Schema::create($this->table, function(Blueprint $table) {
            $table->string('id')->unique()->primary() ;
            $table->string('type') ;
            $table->text('rule') ;
            $table->string('status') ;
            $table->boolean('auto_update') ;
            $table->timestamps() ;
        }) ;
    }

	/**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down() {
        Schema::dropIfExists($this->table) ;
    }
} ;
