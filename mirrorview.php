#!/usr/bin/php
<?php
/* Display mirrorview status
 * (c)2015 Gulf Interstate Engineering <mlott@gie.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Usage:
 *
 * mirrorview (text) - output as text (default)
 * mirrorview html   - output as html
 * mirrorview mail   - Send text/html email
 */
	/* EDIT THESE ACCORDING TO YOUR SITE */
	date_default_timezone_set('America/Chicago');
	/* Path to naviseccli */
	$NAVCLI  = '/opt/Navisphere/bin/naviseccli';
	/* Storage processor name or IP address */
	$SPADDR  = 'hostname';
	/* Secfile Path */
	$SECPATH = '/root';

	/* Who's sending the mail, if your server allows setting this */
	$FROM    = 'vnx@dom.pri';
	/* Recipient */
	$MAILTO  = 'admin@dom.pri';
	/* Optional secondary recipient */
	$CC      = 'cc@dom.pri';
	/* DONE EDITING */

	$status = `$NAVCLI -secfilepath $SECPATH -Address $SPADDR mirror -async -list | grep "Name\|Description\|State\|Faulted\|Condition\|Progress"`;
	$lines = preg_split('/\n/',$status);

	foreach($lines as $line)
	{
		$line  = trim($line);
		$namet  = preg_split('/MirrorView\sName:\s+/',$line);
		$desct  = preg_split('/MirrorView\sDescription:\s+/',$line);
		$istatet = preg_split('/Image\sState:\s+/',$line);
		$statet = preg_split('/MirrorView\sState:\s+/',$line);
		$faultt = preg_split('/MirrorView\sFaulted:\s+/',$line);
		$ifaultt = preg_split('/Image\sFaulted:\s+/',$line);
		$icondt  = preg_split('/Image\sCondition:\s+/',$line);
		$iprogt  = preg_split('/Synchronizing\sProgress\(\%\):\s+/',$line);

		if(@isset($namet[1]))
		{
			$desc = $state = $istate = $fault = $ifault = $icond = $iprog = '';
		}
		@$name   = @isset($namet[1])   ? $namet[1]   : $name;
		@$desc   = @isset($desct[1])   ? $desct[1]   : $desc;
		@$state  = @isset($statet[1])  ? $statet[1]  : $state;
		@$istate = @isset($istatet[1]) ? $istatet[1] : $istate;
		@$fault  = @isset($faultt[1])  ? $faultt[1]  : $fault;
		@$ifault = @isset($ifaultt[1]) ? $ifaultt[1] : $ifault;
		@$icond  = @isset($icondt[1])  ? $icondt[1]  : $icond;
		@$iprog  = @isset($iprogt[1])  ? $iprogt[1]  : $iprog;

		if($name && $desc && $state && $istate && $fault && $ifault && $icond && $iprog)
		{
			$st2 = $state;
			$ist2 = $istate;
			switch($state)
			{
				case 'Synchronizing':
					$state = 4;
					break;
				case 'Synchronized':
					$state = 3;
					break;
				case 'Consistent':
					$state = 2;
					break;
				case 'Active':
					$state = True;
					break;
				default:
					$state = False;
			}
			switch($istate)
			{
				case 'Synchronizing':
					$istate = 3;
					break;
				case 'Synchronized':
					$istate = 2;
					break;
				case 'Consistent':
					$istate = True;
					break;
				default:
					$istate = False;
			}
			switch($icond)
			{
				case 'Updating':
					$icond = 2;
					break;
				default:
					$icond = 1;
			}

			$out[$desc] = array(
				'name'        => $name,
				'description' => $desc,
				'stateinfo'   => $st2,
				'state'       => $state,
				'istateinfo'  => $ist2,
				'istate'      => $istate,
				'faultinfo'   => $fault,
				'fault'       => ($fault == 'YES') ? True : False,
				'ifaultinfo'  => $ifault,
				'ifault'      => ($ifault == 'YES') ? True : False,
				'icond'       => $icond,
				'iprog'       => $iprog
			);
			$name = $desc = $state = $istate = $fault = $ifault = $icond = $iprog = '';
		}
	}

	ksort($out);
	$ts = date('m/d/Y h:i a', time());
	$tbody   = "\nMirrorView Status - $ts\n\n";
	$tbody  .= "\tConsistent    - in sync and up to date\n\tSynchronized  - in sync with no recent change on source\n\tSynchronizing - sync in progress\n\n";
	$tbody  .= "Description                     Mirror Name                                State             Fault   Image State         Image Fault  Image Condition\n";
	$tbody  .= "-----------------------------------------------------------------------------------------------------------------------------------------------------\n";
	$hbody   =<<<END
<html>
<body bgcolor="#CCCCCC">
	<table border="1">
		<tr>
			<th colspan="7">MirrorView Status</th>
		</tr>
		<tr>
			<td colspan="7">Consistent    - in sync and up to date</td>
		</tr>
		<tr>
			<td colspan="7">Synchronized  - in sync with no recent change on source</td>
		</tr>
		<tr>
			<td colspan="7">Synchronizing - sync in progress</td>
		</tr>
		<tr>
			<th class="table-bordered" align="left">Description</th>
			<th class="table-bordered" align="left">Mirror Name</th>
			<th class="table-bordered" align="left">State</th>
			<th class="table-bordered" align="left">Fault</th>
			<th class="table-bordered" align="left">Image State</th>
			<th class="table-bordered" align="left">Image Fault</th>
			<th class="table-bordered" align="left">Image Condition</th>
		</tr>
END;

	foreach($out as $data)
	{
		$istcolor = $stcolor = $fcolor = $ifcolor = $icondcolor = 'black';
		switch((int)$data['state'])
		{
			case 4:
				$stcolor = 'orange';
				break;
			case 3:
				$stcolor = 'yellow';
				break;
			case 2:
				$stcolor = 'blue';
				break;
			case True:
				$stcolor = 'green';
				break;
			default:
				$stcolor = 'red';
		}
		switch((int)$data['istate'])
		{
			case 4:
				$istcolor = 'orange';
				break;
			case 3:
				$istcolor = 'yellow';
				break;
			case 2:
				$istcolor = 'blue';
				break;
			case True:
			case 1:
				$istcolor = 'green';
				break;
			default:
				$istcolor = 'red';
		}
		switch((int)$data['icond'])
		{
			case 2:
				$icondcolor = 'blue';
				break;
			default:
				$icondcolor = 'green';
				break;
		}

		$fcolor  = ($data['fault'] === True) ? 'red' : 'black';
		$ifcolor = ($data['ifault'] === True) ? 'red' : 'black';
		$name  = str_pad($data['name'],43);
		$descr = str_pad($data['description'],32);
		$state = str_pad($data['stateinfo'] . ' (' . $data['state'] . ')',18);
		$istate = str_pad($data['istateinfo'] . ' (' . $data['istate'] . ')',20);
		$fault = str_pad($data['faultinfo'],8);
		$ifault = str_pad($data['ifaultinfo'],13);
		$icond  = ($data['icond'] == 2) ? 'Updating(' . $data['iprog'] . '%)' : 'Normal';

		$addtext =<<<END
$descr$name$state$fault$istate$ifault$icond

END;
		$tbody .= $addtext;

		$name   = trim($name);
		$descr  = trim($descr);
		$state  = trim($state);
		$istate = trim($istate);
		$fault  = trim($fault);
		$ifault = trim($ifault);
		$icond  = trim($icond);

		$addhtml =<<<END

		<tr>
			<td class="table-bordered">$descr</td>
			<td class="table-bordered">$name</td>
			<td class="table-bordered"><font color="$stcolor">$state</font></td>
			<td class="table-bordered"><font color="$fcolor">$fault</td>
			<td class="table-bordered"><font color="$istcolor">$istate</font></td>
			<td class="table-bordered"><font color="$ifcolor">$ifault</td>
			<td class="table-bordered"><font color="$icondcolor">$icond</td>
		</tr>
END;
		$hbody .= $addhtml;
	}

	$hbody .= "\n</table>\n</body>\n</html>";

	switch(@$argv[1])
	{
		case 'mail':
			$mime_boundary = 'Multipart_Boundary_x' . md5(time()) . 'x';
			$headers = "MIME-Version: 1.0\r\n"
				. "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\r\n"
				. "Content-Transfer-Encoding: 7bit\r\n"
				. 'From: MirrorView Status <' . $FROM . ">\r\n"
				. 'X-Mailer: MirrorStatus' . "\r\n"
				. 'Date: ' . date('n/d/Y g:i A') . "\r\n";
				if($CC)
				{
					$headers .= "Cc: $CC\r\n";
				}

			$body = "This is a multi-part message in mime format.\n\n"
				. "--$mime_boundary\n"
				. "Content-Type: text/plain; charset=\"charset=us-ascii\"\n"
				. "Content-Transfer-Encoding: 7bit\n\n"
				. "$tbody\n\n"
				. "--$mime_boundary\n"
				. "Content-Type: text/html; charset=\"UTF-8\"\n"
				. "Content-Transfer-Encoding: 7bit\n\n"
				. "$hbody\n\n"
				. "--$mime_boundary--\n";

			mail($MAILTO,'MirrorView Status ' . $ts,$body,$headers,"-f $FROM");
			break;
		case 'html':
			echo $hbody;
			break;
		case 'text':
		default:
			echo $tbody;
			break;
	}

