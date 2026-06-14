<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataInput\Player\Leveling\EarnedExperience\ListEarnedExperienceDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Leveling\EarnedExperience\ListEarnedExperienceValidator;
use App\Infrastructure\Repository\Leveling\EarnedExperience\EarnedExperienceRepository;
use App\UseCase\Player\Leveling\EarnedExperience\ListEarnedExperienceUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListEarnedExperienceUseCaseTest extends KernelTestCase
{
    public function testItReturnsEntriesOrderedByEarnedAtDesc(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'journal-order');
        $clock = $container->get(ClockInterface::class);

        $older = self::createEntry($container, $player, 'Older', 100, $clock->now()->modify('-2 days'));
        $newer = self::createEntry($container, $player, 'Newer', 300, $clock->now()->modify('-1 hour'));
        $middle = self::createEntry($container, $player, 'Middle', 200, $clock->now()->modify('-1 day'));

        $output = self::buildUseCase($container, $player)->execute(new ListEarnedExperienceDataInput());

        self::assertSame(3, $output->totalCount);
        self::assertCount(3, $output->items);
        self::assertSame($newer->id, $output->items[0]->id);
        self::assertSame($middle->id, $output->items[1]->id);
        self::assertSame($older->id, $output->items[2]->id);
        self::assertSame(300, $output->items[0]->amount);
        self::assertNotNull($output->items[0]->earnedAt);
    }

    public function testItPaginatesResults(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'journal-paginate');
        $clock = $container->get(ClockInterface::class);

        for ($i = 0; 5 > $i; ++$i) {
            self::createEntry($container, $player, sprintf('Entry %d', $i), 50, $clock->now()->modify(sprintf('-%d hours', $i + 1)));
        }

        $useCase = self::buildUseCase($container, $player);

        $page1 = $useCase->execute(new ListEarnedExperienceDataInput(1, 2));
        self::assertSame(5, $page1->totalCount);
        self::assertCount(2, $page1->items);

        $page2 = $useCase->execute(new ListEarnedExperienceDataInput(2, 2));
        self::assertCount(2, $page2->items);
        self::assertNotSame($page1->items[0]->id, $page2->items[0]->id);

        $page3 = $useCase->execute(new ListEarnedExperienceDataInput(3, 2));
        self::assertCount(1, $page3->items);
    }

    public function testItExcludesAnotherPlayersEntries(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createTestPlayer($container, 'journal-isolation-a');
        $playerB = self::createTestPlayer($container, 'journal-isolation-b');
        $clock = $container->get(ClockInterface::class);

        self::createEntry($container, $playerA, 'A entry', 100, $clock->now());
        self::createEntry($container, $playerB, 'B entry', 100, $clock->now());

        $output = self::buildUseCase($container, $playerA)->execute(new ListEarnedExperienceDataInput());

        self::assertSame(1, $output->totalCount);
        self::assertSame('A entry', $output->items[0]->label);
    }

    public function testItRejectsAPerPageAboveTheMaximum(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'journal-invalid');

        try {
            self::buildUseCase($container, $player)->execute(
                new ListEarnedExperienceDataInput(1, ListEarnedExperienceDataInput::MAX_PER_PAGE + 1),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListEarnedExperienceValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('perPage', $e->violations);
        }
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Journal Hero',
        ));
    }

    private static function createEntry(
        ContainerInterface $container,
        PlayerDataModel $player,
        string $label,
        int $amount,
        \DateTimeImmutable $earnedAt,
    ): EarnedExperienceDataModel {
        return $container->get(EarnedExperiencePersisterGateway::class)->create(new EarnedExperienceDataModel(
            $player,
            $label,
            $amount,
            $earnedAt,
            EarnedExperienceSourceTypeRegistry::WORKOUT,
            '01000000000000000000000000',
        ));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListEarnedExperienceUseCase
    {
        $registry = $container->get(ManagerRegistry::class);
        $resolver = new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };

        return new ListEarnedExperienceUseCase(
            new ListEarnedExperienceValidator(),
            $resolver,
            new EarnedExperienceRepository($registry),
        );
    }
}
