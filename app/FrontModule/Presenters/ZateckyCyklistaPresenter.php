<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\ZateckyCyklistaModel;
use K2D\Gallery\Models\ImageModel;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Database\DriverException;
use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;
use Nette\Neon\Neon;

class ZateckyCyklistaPresenter extends BasePresenter
{

	/** @inject */
	public ImageModel $imageModel;

	/** @inject */
	public ZateckyCyklistaModel $zateckyCyklistaModel;

	public function renderDefault(): void
	{
		$this->template->images = $this->imageModel->getImagesByGallery(2);
	}


	public function renderRegistration(): void
	{
	}

	public function renderStartlist(): void
	{
		$this->template->competitors = $this->zateckyCyklistaModel->getRegisteredCompetitors();
	}


	protected function createComponentSignUpForm(): Form
	{
		$form = new Form();

		$form->addText('name', 'Jméno')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Musíte uvést Vaše jméno');

		$form->addText('surname', 'Příjmení')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Musíte uvést Vaše příjmení');

		$form->addInteger('year_of_birth', 'Rok narození')
			->addRule(Form::LENGTH, 'Požadovaná délka jsou %s znaky', 4)
			->addRule(Form::MIN,'Vážně je Vám víc než 100 let? :-)',1921)
			->setRequired('Musíte uvést Váš rok narození');

		$form->addSelect('sex', 'Pohlaví')
			->setPrompt('-------')
			->setRequired('Musíte uvést Vaše pohlaví')
			->setItems([
				'M' => 'Muž',
				'Ž' => 'Žena'
			]);

		$form->addSelect('category', 'Kategorie')
			->setPrompt('-------')
			->setRequired('Musíte si zvolit kategorii')
			->setItems([
				'KLASIK' => 'KLASIK',
				'HOBBY' => 'HOBBY',
				'DĚTI' => 'DĚTI'
			]);

		$form->addText('team', 'Oddíl/město')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 150);

		$form->addEmail('email', 'Emailová adresa')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Musíte uvést Vaši emailovou adresu');

		$form->addCheckbox('agree', 'Souhlasím s využitím osobních údajů za účelem zpracování výsledků závodu.')
			->setHtmlAttribute('class', 'form-control')
			->setRequired('Je potřeba souhlasit s podmínkami');

		$form->addInvisibleReCaptcha('recaptcha')
			->setMessage('Jste opravdu člověk?');

		$form->addSubmit('submit', 'Odeslat přihlášku');

		$form->onSubmit[] = function (Form $form) {
			try {
				$values = $form->getValues(true);

				unset($values['agree']);
				$values['id'] = $this->zateckyCyklistaModel->insert($values)->id;

				if (!empty($values)) {
					$sex = ($values['sex'] === 'M') ? 'Muži' : 'Ženy';

					// send mail
					$latte = new Engine;
					$params = [
						'name' => $values['name'],
						'surname' => $values['surname'],
						'sex' => $sex,
						'birth_year' => $values['year_of_birth'],
						'category' => $values['category'],
						'team' => $values['team']
					];

					$mail = new Message();

					$mail->setFrom('info@hopmantriatlon.cz', 'Žatecký cyklista (přes Hopman web)');
					$mail->addTo($values['email']);
					$mail->setHtmlBody(
						$latte->renderToString(__DIR__ . '/../../Email/zatecky-cyklista.latte', $params),
						__DIR__ . '/../../assets/img/email');
					$parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));


					$mailer = new SmtpMailer([
						'host' => $parameters['mail']['host'],
						'username' => $parameters['mail']['username'],
						'password' => $parameters['mail']['password'],
						'secure' => $parameters['mail']['secure'],
					]);
					$mailer->send($mail);
				}

				$this->flashMessage('Registrace proběhla úspěšně!');
				$this->redirect('this?odeslano=1');

			} catch (DriverException $e) {
				$this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Kontaktujte prosím správce webu na info@hopmantriatlon.cz', 'danger');
			}
		};

		return $form;
	}

}
