import { baseRequestClient, requestClient } from '#/api/request';

export namespace AuthApi {
  /** 登录接口参数（手机号 + 密码） */
  export interface LoginParams {
    mobile: string;
    password: string;
    // 绑定社交登录时，需要传递如下参数
    socialType?: number;
    socialCode?: string;
    socialState?: string;
  }

  /** 登录接口返回值 */
  export interface LoginResult {
    userId: number;
    accessToken: string;
    refreshToken: string;
    expiresTime: string;
    openid?: string;
  }

  /** 手机验证码获取接口参数 */
  export interface SmsCodeParams {
    mobile: string;
    scene: number;
  }

  /** 手机验证码登录接口参数 */
  export interface SmsLoginParams {
    mobile: string;
    code: string;
  }

  /** 重置密码接口参数 */
  export interface ResetPasswordParams {
    password: string;
    mobile: string;
    code: string;
  }

  /** 社交快捷登录接口参数 */
  export interface SocialLoginParams {
    type: number;
    code: string;
    state: string;
  }
}

/** 登录（手机号 + 密码） */
export async function loginApi(data: AuthApi.LoginParams) {
  return requestClient.post<AuthApi.LoginResult>('/member/auth/login', data);
}

/** 刷新 accessToken */
export async function refreshTokenApi(refreshToken: string) {
  return baseRequestClient.post('/member/auth/refresh-token', { refreshToken });
}

/** 退出登录 */
export async function logoutApi(accessToken: string) {
  return baseRequestClient.post(
    '/member/auth/logout',
    {},
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    },
  );
}

/** 获取用户基本信息 */
export async function getUserInfoApi() {
  return requestClient.get('/member/user/get');
}

/** 获取验证码 */
export async function getCaptcha(data: any) {
  return baseRequestClient.post('/system/captcha/get', data);
}

/** 校验验证码 */
export async function checkCaptcha(data: any) {
  return baseRequestClient.post('/system/captcha/check', data);
}

/** 发送手机验证码 */
export async function sendSmsCode(data: AuthApi.SmsCodeParams) {
  return requestClient.post('/member/auth/send-sms-code', data);
}

/** 短信验证码登录 */
export async function smsLogin(data: AuthApi.SmsLoginParams) {
  return requestClient.post<AuthApi.LoginResult>(
    '/member/auth/sms-login',
    data,
  );
}

/** 社交授权的跳转 */
export async function socialAuthRedirect(type: number, redirectUri: string) {
  return requestClient.get('/member/auth/social-auth-redirect', {
    params: {
      type,
      redirectUri,
    },
  });
}

/** 通过短信重置密码 */
export async function smsResetPassword(data: AuthApi.ResetPasswordParams) {
  return requestClient.put('/member/user/reset-password', data);
}

/** 社交快捷登录 */
export async function socialLogin(data: AuthApi.SocialLoginParams) {
  return requestClient.post<AuthApi.LoginResult>(
    '/member/auth/social-login',
    data,
  );
}
