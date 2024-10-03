<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class representing an HTTP rule matching legitimate
 * or malicious behaviors.
 *
 * @property \Carbon\Carbon created_at
 * @property \Carbon\Carbon updated_at
 *
 * @author: Damien MOLINA
 */
class WafRule extends Model {

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'waf_rules' ;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [] ;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	] ;
}
