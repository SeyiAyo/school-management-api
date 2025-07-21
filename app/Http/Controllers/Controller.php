<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="School Management API",
 *     description="API Documentation for School Management System",
 *     @OA\Contact(
 *         email="oluwaseyiayoola97@gmail.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Format a successful JSON response.
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200 OK)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'HttpStatusCode' => $statusCode,
            'data' => $data,
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Format an error JSON response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default: 400 Bad Request)
     * @param array $errors Optional array of validation errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message, int $statusCode = 400, array $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message,
            'HttpStatusCode' => $statusCode,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format an internal server error JSON response.
     *
     * @param string $message Error message
     * @param array $errors Optional array of additional error details
     * @return \Illuminate\Http\JsonResponse
     */
    protected function internalServerError(string $message = 'Internal server error', array $errors = [])
    {
        return $this->error($message, 500, $errors);
    }

    /**
     * Format a 404 not found JSON response.
     *
     * @param string $message Error message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound(string $message = 'Resource not found')
    {
        return $this->error($message, 404);
    }
}
