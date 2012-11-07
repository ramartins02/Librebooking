<?php
/**
Copyright 2012 Nick Korbel

This file is part of phpScheduleIt.

phpScheduleIt is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

phpScheduleIt is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with phpScheduleIt.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(ROOT_DIR . 'WebServices/BookingsWebService.php');

class BookingsWebServiceTests extends TestBase
{
	/**
	 * @var FakeRestServer
	 */
	private $server;

	/**
	 * @var BookingsWebService
	 */
	private $service;

	/**
	 * @var IReservationViewRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	private $reservationViewRepository;

	/**
	 * @var WebServiceUserSession
	 */
	private $userSession;

	/**
	 * @var Date
	 */
	private $defaultStartDate;

	/**
	 * @var Date
	 */
	private $defaultEndDate;

	public function setup()
	{
		parent::setup();

		$this->userSession = new WebServiceUserSession(123);

		$this->defaultStartDate = Date::Now();
		$this->defaultEndDate = Date::Now()->AddDays(14);

		$this->server = new FakeRestServer();
		$this->server->SetSession($this->userSession);

		$this->reservationViewRepository = $this->getMock('IReservationViewRepository');

		$this->service = new BookingsWebService($this->server, $this->reservationViewRepository);
	}

	public function testDefaultsToNextTwoWeeksAndCurrentUser()
	{
		$this->server->SetQueryString(WebServiceQueryStringKeys::USER_ID, null);
		$this->server->SetQueryString(WebServiceQueryStringKeys::START_DATE_TIME, null);
		$this->server->SetQueryString(WebServiceQueryStringKeys::END_DATE_TIME, null);

		$userId = $this->userSession->UserId;
		$reservations = array();

		$this->reservationViewRepository->expects($this->once())
				->method('GetReservationList')
				->with($this->equalTo($this->defaultStartDate), $this->equalTo($this->defaultEndDate),
					   $this->equalTo($userId))
				->will($this->returnValue($reservations));

		$this->service->GetBookings();

		$expectedResponse = new BookingsResponse();
		$expectedResponse->AddReservations($reservations, $this->server);
		$this->assertEquals($expectedResponse, $this->server->_LastResponse);
	}

	public function testWhenUserIdIsForAnotherUser()
	{
		$userId = 9999;
		$user = new User();
		$user->WithId($userId);

		$this->server->SetQueryString(WebServiceQueryStringKeys::USER_ID, $userId);

		$this->reservationViewRepository->expects($this->once())
				->method('GetReservationList')
				->with($this->anything(), $this->anything(), $this->equalTo($userId))
				->will($this->returnValue(array()));

		$this->service->GetBookings();
	}

	public function testWhenResourceIdIsProvided()
	{
		$resourceId = 12345;

		$this->server->SetQueryString(WebServiceQueryStringKeys::RESOURCE_ID, $resourceId);

		$this->reservationViewRepository->expects($this->once())
				->method('GetReservationList')
				->with($this->equalTo($this->defaultStartDate), $this->equalTo($this->defaultEndDate),
					   $this->isNull(), $this->isNull(),
					   $this->isNull(), $this->equalTo($resourceId))
				->will($this->returnValue(array()));

		$this->service->GetBookings();
	}

	public function testWhenScheduleIdIsProvided()
	{
		$scheduleId = 12346;

		$this->server->SetQueryString(WebServiceQueryStringKeys::SCHEDULE_ID, $scheduleId);

		$this->reservationViewRepository->expects($this->once())
				->method('GetReservationList')
				->with($this->equalTo($this->defaultStartDate), $this->equalTo($this->defaultEndDate),
					   $this->isNull(), $this->isNull(),
					   $this->equalTo($scheduleId), $this->isNull())
				->will($this->returnValue(array()));

		$this->service->GetBookings();
	}
}

?>