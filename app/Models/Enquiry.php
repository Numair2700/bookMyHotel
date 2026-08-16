<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    // Eloquent pluralises "enquiry" correctly to "enquiries", set for clarity.
    protected $table = 'enquiries';

    protected $fillable = ['name', 'email', 'subject', 'message', 'status'];
}
