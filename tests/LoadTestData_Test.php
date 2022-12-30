<?php declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestBase.php';

use App\Entity\Text;
use App\Entity\Language;

// This isn't really a test ... it just loads the database with data.
// Still reasonable to keep as a test though as it needs to always
// work.
final class LoadTestData_Test extends DatabaseTestBase
{

    /**
     * @group dev:data:clear
     */
    public function test_clea_dev_data(): void {
        // the db clear in DatabaseTestBase wipes everything.
        $this->assertEquals(1, 1, 'Dummy test so phpunit is happy :-)');
    }

    /**
     * @group dev:data:load
     */
    public function test_load_dev_data(): void
    {
        $this->load_languages();
        $this->load_spanish_words();

        $story = "Érase una vez una preciosa niña que siempre llevaba una capa roja con capucha para protegerse del frío. Por eso, todo el mundo la llamaba Caperucita Roja.

Caperucita vivía en una casita cerca del bosque. Un día, la mamá de  Caperucita le dijo:

– Hija mía, tu abuelita está enferma. He preparado una cestita con tortas y un tarrito de miel para que se la lleves. ¡Ya verás qué contenta se pone!

– ¡Estupendo, mamá! Yo también tengo muchas ganas de ir a visitarla – dijo Caperucita saltando de alegría.

Cuando Caperucita se disponía  a salir de casa, su mamá, con gesto un poco serio, le hizo una advertencia:

– Ten mucho cuidado, cariño. No te entretengas con nada y no hables con extraños. Sabes que en el bosque vive el lobo y es muy peligroso. Si ves que aparece, sigue tu camino sin detenerte.

– No te preocupes, mamita – dijo la niña -. Tendré en cuenta todo lo que me dices.

– Está bien – contestó la mamá, confiada –. Dame un besito y no tardes en regresar.

– Así lo haré, mamá – afirmó de nuevo Caperucita diciendo adiós con su manita mientras se alejaba.";

        $t = new Text();
        $t->setTitle("Caperucita Roja");
        $t->setText($story);
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $this->load_spanish_texts();

        $this->load_french_data();

        $this->assertEquals(1, 1, 'Dummy test so phpunit is happy :-)');
    }

}
