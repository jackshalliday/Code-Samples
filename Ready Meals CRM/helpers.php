<?php

function isActive($path, $class = 'active')
{
    return (Request::is($path)) ? $class : '';
}

function getUserName()
{
	$user = \Auth::user();
    return $user->name . ' ' . $user->user_surname;
}