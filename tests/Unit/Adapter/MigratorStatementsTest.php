<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Adapter;

use Barbier\Adapter\Persistence\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorStatementsTest extends TestCase
{
    /** @happy deux instructions, commentaire ignoré */
    public function testDecoupageSimple(): void
    {
        self::assertSame(['CREATE TABLE a (x INT)', 'DROP TABLE b'], Migrator::statements("-- commentaire\nCREATE TABLE a (x INT);\nDROP TABLE b;\n"));
    }

    /** @edge « ; », « -- » et apostrophes doublées DANS une chaîne ne coupent pas */
    public function testPointVirguleDansUneChaine(): void
    {
        $sql = "INSERT INTO t VALUES ('base légale ; aucun traceur -- vraiment', 'l''alun');\nSELECT 1;";
        self::assertSame(["INSERT INTO t VALUES ('base légale ; aucun traceur -- vraiment', 'l''alun')", 'SELECT 1'], Migrator::statements($sql));
    }

    /** @edge apostrophe échappée par antislash et fichier sans « ; » final */
    public function testAntislashEtSansPointVirguleFinal(): void
    {
        self::assertSame(["INSERT INTO t VALUES ('l\\'alun;x')"], Migrator::statements("INSERT INTO t VALUES ('l\\'alun;x')"));
    }

    /** @negative fichier vide ou seulement des commentaires */
    public function testVide(): void
    {
        self::assertSame([], Migrator::statements("-- rien\n\n"));
    }
}
