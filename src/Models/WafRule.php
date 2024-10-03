<?php

namespace Pythagus\LaravelWaf\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class representing an HTTP rule matching legitimate
 * or malicious behaviors.
 *
 * @property string id
 * @property string type
 * @property string rule
 * @property string status
 * @property boolean auto_update
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
	protected $fillable = [
		'id', 'type', 'rule', 'status', 'auto_update'
	] ;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
		'auto_update' => 'boolean',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	] ;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false ;

	/**
	 * Determine whether the rule is automatically
	 * updated.
	 * 
	 * @return bool
	 */
	public function isAutoUpdated() {
		return boolval($this->auto_update) ;
	}
}
