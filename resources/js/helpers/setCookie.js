export function setCookie(name, value, hours = 12) {
  const now = new Date();
  now.setTime(now.getTime() + hours * 60 * 60 * 1000); // 12 hours
  const expires = "expires=" + now.toUTCString();
  document.cookie = `${name}=${value}; ${expires}; path=/`;
}

export function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? match[2] : null;
}