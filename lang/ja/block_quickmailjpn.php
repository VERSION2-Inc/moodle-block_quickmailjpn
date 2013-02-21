<?PHP // $Id: block_quickmailjpn.php 4 2012-04-28 18:19:08Z yama $ 
      // block_quickmailjpn.php - created with Moodle 2.0 dev (2007101508)

$string['action'] = '操作';
$string['areyousure'] = '本当に履歴中のすべてのメールを削除してもよろしいですか?';
$string['attachment'] = '添付';
$string['attachmentalt'] = 'メールに添付ファイルを追加する';
$string['attachmenterror'] = '有効な添付ファイルではありません! 次のファイルは存在していません： <strong>{$a}</strong>';
$string['attachmentoptional'] = '添付 (任意)';
$string['blockname'] = $string['pluginname'] = 'クイック携帯メールJPN';
$string['check'] = 'すべてを選択';
$string['chooseafile'] = 'ファイルを選択';
$string['clearhistory'] = 'すべての履歴をクリア';
$string['compose'] = '作成';
$string['date'] = '日時';
$string['delete'] = '削除';
$string['deletefail'] = '削除が失敗しました。';
$string['deletesuccess'] = '正常に削除されました。';
$string['email'] = 'メール';
$string['emailfail'] = 'メールエラー:';
$string['emailfailerror'] = 'エラーが発生したため、下記のユーザにメール送信されませんでした ...';
$string['emailstop'] = 'メールアドレスが無効にされています:';
$string['history'] = '履歴';
$string['log'] = 'ログ';
$string['messageerror'] = 'メッセージを入力してください!';
$string['nogroupmembers'] = 'グループメンバーなし';
$string['notingroup'] = 'グループ外';
$string['sendemail'] = 'メールを送信する';
$string['sendconfirmationemail'] = '確認メールを送信する';
$string['subjecterror'] = '題名を入力してください!';
$string['successfulemail'] = 'メールが正常に送信されました。';
$string['to'] = 'To';
$string['toerror'] = 'メールの受信者を選択してください!';
$string['uncheck'] = 'すべての選択を解除';
$string['confirmdelete'] = '本当にこのメール履歴を削除してもよろしいですか？';

$string['allowstudents'] = '{$a} にクイックメールの使用を許可する'; // ORPHANED

$string['quickmail:cansend'] = 'クイックメールでメールを送信できる';
$string['quickmailjpn:cansend'] = 'JISメールを送信する';
$string['quickmailjpn:view'] = 'JISメールを受信する';
$string['mobilephone'] = '携帯メールアドレス';
$string['mymobilephone'] = '私の携帯メールアドレス';
$string['entermobilephone'] = 'あなたの携帯メールアドレスを入力してください：';
$string['notyet'] = '未設定';
$string['checking'] = 'チェック中';
$string['confirmed'] = '確認済';
$string['user-notyet'] = '未設定';
$string['user-checking'] = 'チェック中';
$string['user-confirmed'] = '確認済';
$string['block-notyet'] = '{$a} &mdash; ここをクリック';
$string['block-checking'] = '{$a} or 再設定';
$string['block-confirmed'] = '{$a} &mdash; 更新';
$string['quickmailJPN_mobile_email'] = '携帯メールアドレス';
$string['sentcheckemail'] = '携帯へメールが送信されました。携帯で受信して、メール内のリンクを開いてください。';
$string['presentstatus'] = '現在の状態：';
$string['statusexplanation'] = '<ol class="statusexplanation">
<li>携帯メールアドレスを入力して確認メールを送信してください。<br />
<small>（携帯メールが無ければ普段使用するメールアドレスを入力してください。）</small></li>
<li>携帯に送られたメールのリンクを開いて確認を完了してください。</li>
<p>携帯のメールアドレスが変わった場合は新しいアドレスを上記操作で再登録してください。</p>
<p><small>※メールフィルタを設定している方は、{$a} からのメールを許可するように事前に設定してください。</small></p>';
$string['select'] = '選択';
$string['name'] = '名前';
$string['status'] = '状態';
$string['adminemailaddress'] = '確認メール送信元アドレス';
$string['adminemailaddressexplanation'] = '必ず設定してください。このアドレスは確認メールの From フィールドに使用されます。';
$string['adminmailsubject']     = '確認メール題名';
$string['adminmailsubjecttext'] = 'メールアドレス確認';
$string['adminmailmessage']     = '確認メール内容';
$string['adminmailmessagetext'] = 'これはメールアドレスを確認するための自動メールです。
下記のリンクを開いてサイトにアクセスし、表示されるメッセージに従ってください。';
$string['sortorder'] = 'ソート順';
$string['sortorderdesc'] = 'メール作成ページで、この順に従って行がソートされます。';
$string['fromerror'] = 'Fromを入力してください!';
$string['san'] = 'さん';

$string['configtitle'] = 'ブロックタイトル';
$string['configexplanation'] = '説明文';
$string['explanation'] = '<div class="explanation">
ここであなたの携帯メールアドレスを設定してください。 
<strong>※メールアドレスを変更した場合は忘れずに更新してください。</strong><br />
教員は講義に関する重要なメールやお知らせをこのアドレス宛に送ります。
</div>';
