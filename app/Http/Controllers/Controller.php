<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;
#[OA\Info(
    version: "1.0.0",
    title: "Ethiojobs Academy API",
    description: "API Documentation for Ethiojobs Academy Learning Platform"
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Local Development Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
    //
}
