<?php

test('the default ai provider resolves to ollama locally', function () {
    expect(config('ai.default'))->toBe('ollama');
});

test('both the local and production providers are configured', function () {
    expect(config('ai.providers.ollama.driver'))->toBe('ollama');
    expect(config('ai.providers.anthropic.driver'))->toBe('anthropic');
});
