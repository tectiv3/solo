<?php

namespace AaronFrancis\Solo\Support;

use Closure;
use Illuminate\Foundation\Console\CliDumper;
use Illuminate\Foundation\Console\CliDumper as LaravelCliDumper;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;
use Symfony\Component\VarDumper\Dumper\ServerDumper;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

readonly class CustomDumper
{
    public static function register($basePath, $compiledViewPath): static
    {
        return new static($basePath, $compiledViewPath);
    }

    public static function dumpServerHost(): string
    {
        return config()->string('solo.dumpServerHost', 'tcp://127.0.0.1:9912');
    }

    public function __construct(public string $basePath, public string $compiledViewPath)
    {
        $cloner = new VarCloner;
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        // We only use this dumper to get the dump source and add it to the context.
        $fake = $this->makeSourceResolvingDumper();

        // This is the original dumper, in case the server is not running or not reachable.
        $fallback = $this->makeFallbackDumper();

        $server = new ServerDumper(static::dumpServerHost(), $fallback);

        VarDumper::setHandler(function (mixed $var) use ($cloner, $server, $fallback, $fake) {
            $data = $cloner->cloneVar($var)->withContext([
                'dumpSource' => $fake->resolveDumpSource()
            ]);

            try {
                $server->dump($data);
            } catch (Throwable $e) {
                $fallback->dump($data);
            }
        });
    }

    protected function makeSourceResolvingDumper(): CliDumper
    {
        $output = new StreamOutput(fopen('php://memory', 'w'));

        return new LaravelCliDumper($output, $this->basePath, $this->compiledViewPath);
    }

    protected function makeFallbackDumper(): DataDumperInterface
    {
        return new class implements DataDumperInterface {
            private ?Closure $original;

            public function __construct()
            {
                // Passing null explicitly just so we can get the original one out.
                $this->original = VarDumper::setHandler(null);
            }

            public function dump(Data $data): void
            {
                if (is_callable($this->original)) {
                    call_user_func($this->original, $data->getValue());
                } else {
                    print_r($data->getValue());
                }
            }
        };
    }
}
