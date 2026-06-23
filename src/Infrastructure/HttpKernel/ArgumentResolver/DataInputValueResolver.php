<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\ArgumentResolver;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Infrastructure\Exception\DataInputMappingException;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class DataInputValueResolver implements ValueResolverInterface
{
    private const string GENERIC_ERROR_MESSAGE = 'The data provided seems to be invalid.';

    public function __construct(
        private DenormalizerInterface $denormalizer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return iterable<int, DataInputInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ([] === $argument->getAttributesOfType(MapDataInput::class)) {
            return [];
        }

        $type = $argument->getType();
        if (null === $type || false === is_subclass_of($type, DataInputInterface::class)) {
            throw new \LogicException('The #[MapDataInput] attribute can only target arguments typed as a class implementing DataInputInterface');
        }

        $data = array_replace(
            $request->query->all(),
            $this->extractBodyData($request),
        );

        try {
            $input = $this->denormalizer->denormalize(
                $data,
                $type,
                'csv',
                ['filter_bool' => true],
            );
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to map request data to "%s".', $argument->getType()));
            throw new DataInputMappingException(self::GENERIC_ERROR_MESSAGE, $throwable);
        }

        return [$input];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractBodyData(Request $request): array
    {
        $bodyParams = $request->request->all();
        if ([] !== $bodyParams) {
            return $bodyParams;
        }

        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Request payload is not valid JSON.');
            throw new DataInputMappingException(self::GENERIC_ERROR_MESSAGE, $exception);
        }

        if (false === is_array($decoded)) {
            $this->logger->error('Request payload must be a JSON object.');
            throw new DataInputMappingException(self::GENERIC_ERROR_MESSAGE);
        }

        return $decoded;
    }
}
