<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ApiCompanyController extends Controller
{
    public function show(): JsonResponse
    {
        $company = Company::first();

        if (! $company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'city' => $company->city,
                'state' => $company->state,
                'country' => $company->country,
                'currency_code' => $company->currency_code,
                'currency_symbol' => $company->currency_symbol,
                'tax_number' => $company->tax_number,
                'financial_year_start' => $company->financial_year_start,
            ],
        ]);
    }
}
