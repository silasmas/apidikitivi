<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateYoutubeAccessTokensTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// Base créée à partir d'un dump SQL : ne pas recréer une table existante.
		if (Schema::hasTable('youtube_access_tokens')) {
		    return;
		}

		Schema::create('youtube_access_tokens', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('access_token');
			$table->timestamp('created_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		// Table issue du dump SQL de production : un rollback ne doit jamais la supprimer.
		// Schema::dropIfExists('youtube_access_tokens');
	}

}
