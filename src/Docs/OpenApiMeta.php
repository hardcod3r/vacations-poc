<?php

declare(strict_types=1);

namespace Docs;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Vacation Management API', version: '1.0.0')]
#[OA\Server(url: '/api/v1', description: 'API V1')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
#[OA\Tag(name: 'auth', description: 'Authentication and user management endpoints')]
#[OA\Tag(name: 'employees', description: 'Employee management endpoints')]
#[OA\Tag(name: 'vacations', description: 'Vacation management endpoints')]
#[OA\Tag(name: 'health', description: 'Health check endpoint')]
final class OpenApiMeta {}
