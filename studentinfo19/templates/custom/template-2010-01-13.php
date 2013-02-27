<?php 
include ('access_context.php'); 
require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php'); 

$details = $mis->Execute('SELECT * FROM FES.MOODLE_PEOPLE WHERE to_char("STUDENT_ID") = to_char('.$user->idnumber.')');


// print some personal info about student.

print_heading("$user->firstname $user->lastname");

echo '<div class="generalbox" id="ilp-student_info-overview">'; 
echo '<table class="generalbox" cellspacing="5" cellpadding="5">';
echo '<tr><th scope="row">Student ID</th><td>'.$details->fields['STUDENT_ID'].'</td></tr>';
echo '<tr><th scope="row">Email</th><td>'.$details->fields['EMAIL'].'</td></tr>';
echo '<tr><th scope="row">Gender</th><td>'.$details->fields['GENDER'].'</td></tr>';
echo '<tr><th scope="row">Age</th><td>'.$details->fields['AGE'].' ('.$details->fields['DATE_OF_BIRTH'].')</td></tr>';
echo '<tr><th scope="row">EMA Status</th><td>'.$details->fields['EMA_STATUS'].'</td></tr>';
echo '<tr><th scope="row" valign="top">Address</th><td>'.$details->fields['ADDRESS_LINE_1'].'<br />'.$details->fields['ADDRESS_LINE_2'].'<br />'.$details->fields['ADDRESS_LINE_3'].'<br />'.$details->fields['TOWN'].'<br />'.$details->fields['REGION'].'</td></tr>';
echo '<tr><th scope="row">Postcode</th><td>'.$details->fields['POST_CODE'].'</td></tr>';
echo '<tr><th scope="row">Mobile</th><td>'.$details->fields['MOBILE_PHONE_NUMBER'].'</td></tr>';
echo '<tr><th scope="row">Telephone</th><td>'.$details->fields['TELEPHONE'].'</td></tr>';
echo '<tr><th scope="row">Next of Kin</th><td>'.$details->fields['NEXT_OF_KIN'].'</td></tr>';
echo '<tr><th scope="row">Kin Contact</th><td>'.$details->fields['NOK_CONTACT_NO'].'</td></tr>';
echo '</table>';
// nkowald - 2010-06-29 - Moved this info from the main page to here
echo '<table>
<tr>
<td class="block_lpr_ilp_container">
        </td>
        <td rowspan="8">
            <table>
                <tr>
                    <th colspan="2">Assessment</th>
                </tr>
                <tr>
                    <td>Initial Assessment</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Learning Style</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Literacy DA level</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Numeracy DA level</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>ICT DA level</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </td>
</tr>
</table>';
// nkowald

echo '</div>';

if($config->ilp_show_student_info == '1' && ($view == 'info' || $view == 'all')) {
    echo '<div class="generalbox" id="ilp-student_info-overview">'; 
    display_ilp_student_info($user->id,$courseid); 
    echo '</div>';
}

if($config->ilp_show_personal_reports == 1 && ($view == 'personal' || $view == 'all')) { 
    echo '<div class="generalbox" id="ilp-personal_report-overview">';
    display_ilp_personal_report($user->id,$courseid);
    echo '</div>';
}
 
if($config->ilp_show_subject_reports == 1 && ($view == 'subject' || $view == 'all')) {
    echo '<div class="generalbox" id="ilp-subject_report-overview">';
    display_ilp_subject_report($user->id,$courseid);
    echo '</div>';  
}

?>