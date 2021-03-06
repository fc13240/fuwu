<?php namespace Royalcms\Component\Database\Console;

use Royalcms\Component\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Royalcms\Component\Database\ConnectionResolverInterface as Resolver;

class SeedCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'db:seed';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Seed the database with records';

	/**
	 * The connection resolver instance.
	 *
	 * @var  \Royalcms\Component\Database\ConnectionResolverInterface
	 */
	protected $resolver;

	/**
	 * Create a new database seed command instance.
	 *
	 * @param  \Royalcms\Component\Database\ConnectionResolverInterface  $resolver
	 * @return void
	 */
	public function __construct(Resolver $resolver)
	{
		parent::__construct();

		$this->resolver = $resolver;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->resolver->setDefaultConnection($this->getDatabase());

		$this->getSeeder()->run();
	}

	/**
	 * Get a seeder instance from the container.
	 *
	 * @return \Royalcms\Component\Database\Seeder
	 */
	protected function getSeeder()
	{
		$class = $this->resolver->make($this->input->getOption('class'));

		return $class->setContainer($this->royalcms)->setCommand($this);
	}

	/**
	 * Get the name of the database connection to use.
	 *
	 * @return string
	 */
	protected function getDatabase()
	{
		$database = $this->input->getOption('database');

		return $database ?: $this->resolver['config']['database.defaultconnection'];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'),

			array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'),
		);
	}

}
