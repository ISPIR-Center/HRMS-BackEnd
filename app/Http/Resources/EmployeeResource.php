<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'employee_no' => $this->employee_no,
            'suffix' => $this->suffix,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email_address' => $this->email_address,
            'mobile_no' => $this->mobile_no,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'google_scholar_link' => $this->google_scholar_link,
            'employment_type' => $this->employmentType->employment_type,
            'classification' => $this->classification->classification,
            'office' => $this->office->office_name,
        ];
    }

}
