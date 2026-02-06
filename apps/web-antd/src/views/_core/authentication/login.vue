<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';

import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

import { AuthenticationLogin, Verification, z } from '@vben/common-ui';
import { isCaptchaEnable } from '@vben/hooks';
import { $t } from '@vben/locales';

import {
  checkCaptcha,
  getCaptcha,
  socialAuthRedirect,
} from '#/api/core/auth';
import { useAuthStore } from '#/store';

defineOptions({ name: 'Login' });

const { query } = useRoute();
const authStore = useAuthStore();
const captchaEnable = isCaptchaEnable();

const loginRef = ref();
const verifyRef = ref();

const captchaType = 'blockPuzzle';

/** 处理登录 — client 端使用 mobile + password */
async function handleLogin(values: any) {
  if (captchaEnable) {
    verifyRef.value.show();
    return;
  }
  await authStore.authLogin('username', {
    mobile: values.mobile,
    password: values.password,
  });
}

async function handleVerifySuccess({ captchaVerification }: any) {
  try {
    const formValues = await loginRef.value.getFormApi().getValues();
    await authStore.authLogin('username', {
      mobile: formValues.mobile,
      password: formValues.password,
      captchaVerification,
    });
  } catch (error) {
    console.error('Error in handleLogin:', error);
  }
}

const redirect = query?.redirect;
async function handleThirdLogin(type: number) {
  if (type <= 0) {
    return;
  }
  try {
    const redirectUri = `${
      location.origin
    }/auth/social-login?${encodeURIComponent(
      `type=${type}&redirect=${redirect || '/'}`,
    )}`;
    window.location.href = await socialAuthRedirect(type, redirectUri);
  } catch (error) {
    console.error('Third-party login failed:', error);
  }
}

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: 'Please enter mobile number',
      },
      fieldName: 'mobile',
      label: 'Mobile',
      rules: z.string().min(1, { message: 'Please enter mobile number' }),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        placeholder: $t('authentication.passwordTip'),
      },
      fieldName: 'password',
      label: $t('authentication.password'),
      rules: z
        .string()
        .min(1, { message: $t('authentication.passwordTip') }),
    },
  ];
});
</script>

<template>
  <div>
    <AuthenticationLogin
      ref="loginRef"
      :form-schema="formSchema"
      :loading="authStore.loginLoading"
      @submit="handleLogin"
      @third-login="handleThirdLogin"
    />
    <Verification
      ref="verifyRef"
      v-if="captchaEnable"
      :captcha-type="captchaType"
      :check-captcha-api="checkCaptcha"
      :get-captcha-api="getCaptcha"
      :img-size="{ width: '400px', height: '200px' }"
      mode="pop"
      @on-success="handleVerifySuccess"
    />
  </div>
</template>
