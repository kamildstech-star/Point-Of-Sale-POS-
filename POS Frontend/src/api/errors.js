export function errorMessage(error) {
  if (error.response?.status === 419) return 'Your session expired. Please sign in again.';
  if (error.response?.status === 429) return 'Too many attempts. Please wait a minute and try again.';
  return error.response?.data?.message || 'Unable to connect. Please try again.';
}
