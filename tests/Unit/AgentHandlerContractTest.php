<?php

use App\Models\Agent;
use App\Screening\AgentHandler;
use Illuminate\Database\Eloquent\Model;

test('the agent handler contract exposes a single run method', function () {
    $methods = (new ReflectionClass(AgentHandler::class))->getMethods();

    expect($methods)->toHaveCount(1)
        ->and($methods[0]->getName())->toBe('run');
});

test('run accepts an eloquent model and returns an agent', function () {
    $run = new ReflectionMethod(AgentHandler::class, 'run');

    expect($run->getNumberOfParameters())->toBe(1)
        ->and((string) $run->getParameters()[0]->getType())->toBe(Model::class)
        ->and((string) $run->getReturnType())->toBe(Agent::class);
});
