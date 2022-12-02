<?php
namespace Admin;


class Logout
{
	function __construct()
	{
		session_destroy();
		header("Location: ./");
	}

}
