const cookieNotice = document.getElementById('cookie-notice');
const cookieNoticeDismiss = document.getElementById('cookie-notice-dismiss');
const cookieNoticePreference = 'ccai_cookie_notice_seen';

if (cookieNotice && cookieNoticeDismiss) {
  const noticeWasSeen = document.cookie
    .split(';')
    .some(cookie => cookie.trim() === `${cookieNoticePreference}=1`);

  if (noticeWasSeen) {
    cookieNotice.hidden = true;
  }

  cookieNoticeDismiss.addEventListener('click', () => {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${cookieNoticePreference}=1; Max-Age=15552000; Path=/; SameSite=Lax${secure}`;
    cookieNotice.hidden = true;
  });
}
