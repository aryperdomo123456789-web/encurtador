<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

final class ApiDocumentationController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'MElink API',
                'version' => '1.0.0',
                'description' => 'Links de marca, campanhas e eventos de conversão do MElink.',
            ],
            'servers' => [
                ['url' => rtrim((string) config('app.url'), '/')],
            ],
            'security' => [['bearerAuth' => []]],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'MElink token',
                    ],
                ],
                'parameters' => [
                    'IdempotencyKey' => [
                        'name' => 'Idempotency-Key',
                        'in' => 'header',
                        'required' => true,
                        'schema' => ['type' => 'string', 'minLength' => 16, 'maxLength' => 100],
                        'description' => 'Chave única por operação de escrita. Repetições devolvem a mesma resposta.',
                    ],
                ],
                'schemas' => [
                    'Link' => [
                        'type' => 'object',
                        'required' => ['id', 'short_url', 'long_url', 'status'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'format' => 'int64'],
                            'short_url' => ['type' => 'string', 'format' => 'uri'],
                            'long_url' => ['type' => 'string', 'format' => 'uri'],
                            'status' => ['type' => 'string', 'enum' => ['active', 'expired', 'deleted', 'queued']],
                            'campaign' => ['type' => 'string', 'nullable' => true],
                            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                    'Error' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/api/v1/openapi.json' => [
                    'get' => [
                        'summary' => 'Obter este contrato OpenAPI',
                        'security' => [],
                        'responses' => ['200' => ['description' => 'Contrato OpenAPI em JSON.']],
                    ],
                ],
                '/api/v1/me' => [
                    'get' => [
                        'summary' => 'Obter o contexto do token e do workspace',
                        'responses' => ['200' => ['description' => 'Contexto autenticado.'], '401' => ['description' => 'Token inválido.']],
                    ],
                ],
                '/api/v1/links' => [
                    'get' => [
                        'summary' => 'Listar links do workspace',
                        'parameters' => [['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']], ['name' => 'workspace_id', 'in' => 'query', 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'Lista de links.']],
                    ],
                    'post' => [
                        'summary' => 'Criar link',
                        'parameters' => [['$ref' => '#/components/parameters/IdempotencyKey']],
                        'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['long_url'], 'properties' => ['long_url' => ['type' => 'string', 'format' => 'uri'], 'custom_slug' => ['type' => 'string'], 'title' => ['type' => 'string'], 'workspace_id' => ['type' => 'integer']]]]]],
                        'responses' => ['201' => ['description' => 'Link criado.'], '422' => ['description' => 'Dados inválidos.']],
                    ],
                ],
                '/api/v1/links/{link}' => [
                    'parameters' => [['name' => 'link', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'get' => ['summary' => 'Consultar um link', 'responses' => ['200' => ['description' => 'Link.'], '404' => ['description' => 'Não encontrado.']]],
                    'patch' => ['summary' => 'Alterar o destino de um link', 'parameters' => [['$ref' => '#/components/parameters/IdempotencyKey']], 'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['long_url'], 'properties' => ['long_url' => ['type' => 'string', 'format' => 'uri']]]]]], 'responses' => ['200' => ['description' => 'Link atualizado.']]],
                    'delete' => ['summary' => 'Excluir um link', 'parameters' => [['$ref' => '#/components/parameters/IdempotencyKey']], 'responses' => ['204' => ['description' => 'Link excluído.']]],
                ],
                '/api/v1/links/{link}/analytics' => [
                    'get' => ['summary' => 'Consultar analytics do link', 'parameters' => [['name' => 'link', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']], ['name' => 'days', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 365]]], 'responses' => ['200' => ['description' => 'Métricas do link.']]],
                ],
                '/api/v1/events' => [
                    'post' => ['summary' => 'Registrar evento de conversão', 'parameters' => [['$ref' => '#/components/parameters/IdempotencyKey']], 'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['link_id', 'event_name'], 'properties' => ['link_id' => ['type' => 'integer'], 'event_name' => ['type' => 'string'], 'value' => ['type' => 'number'], 'currency' => ['type' => 'string']]]]]], 'responses' => ['202' => ['description' => 'Evento aceito.']]],
                ],
            ],
        ]);
    }
}
