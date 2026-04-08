import type { PageParam, PageResult } from '@vben/request';

import { requestClient } from '#/api/request';

export type RangeType = '1d' | '7d' | '30d' | 'custom';

export interface AccountSummary {
  balance: string;
  totalCharged: string;
  todayCalls: number;
  todayChargeAmount: string;
  lastCall?: {
    traceId: string;
    apiCode: string;
    apiName: string;
    requestTime: string;
    httpStatus: number;
    responseStatus: string;
    deductAmount: string;
  } | null;
}

export interface AccountCredential {
  clientId: string;
  clientSecret: string;
  clientIdMasked: string;
  clientSecretMasked: string;
}

export interface ApiSchemaField {
  field: string;
  type: string;
  required: boolean;
  description: string;
}

export interface AccountApiItem {
  apiCode: string;
  apiName: string;
  description?: string;
  method: string;
  path: string;
  status: 'ENABLED' | 'DISABLED';
  rateLimit?: string;
  requestSchema: ApiSchemaField[];
  responseSchema: ApiSchemaField[];
  requestExample?: string;
  responseExample?: string;
}

export interface TrendPoint {
  time: string;
  deductAmount: string;
  callCount: number;
  successCount: number;
}

export interface TrendQuery {
  range: RangeType;
  startTime?: string;
  endTime?: string;
}

export interface ChargeRecord {
  chargeTime: string;
  apiCode: string;
  apiName: string;
  price: string;
  balanceBefore: string;
  balanceAfter: string;
  chargeStatus: number;
  failureReason?: string;
  remark?: string;
  traceId: string;
}

export interface ChargeQuery extends PageParam {
  type?: 'DEDUCT' | 'RECHARGE';
  range?: RangeType;
  apiCode?: string;
  startTime?: string;
  endTime?: string;
}

export interface LogRecord {
  requestTime: string;
  apiCode: string;
  apiName: string;
  httpStatus: number;
  responseStatus: string;
  responseTimeMs: number;
  deductAmount: string;
  traceId: string;
}

export interface LogQuery extends PageParam {
  range?: RangeType;
  apiCode?: string;
  status?: 'SUCCESS' | 'FAIL';
  startTime?: string;
  endTime?: string;
}

export interface HeaderPair {
  name: string;
  value: string;
}

export interface LogDetail {
  requestTime: string;
  apiCode: string;
  apiName: string;
  httpStatus: number;
  responseStatus: string;
  responseTimeMs: number;
  requestIp: string;
  deductAmount: string;
  chargeStatus: number;
  requestHeaders: HeaderPair[];
  requestParams?: string;
  requestBody?: string;
  responseBody?: string;
  chargeRecord?: ChargeRecord | null;
}

export function fetchAccountSummary() {
  return requestClient.get<AccountSummary>('/member/account/summary');
}

export function fetchAccountCredential() {
  return requestClient.get<AccountCredential>('/platform/app/account/credentials');
}

export function fetchAccountApis() {
  return requestClient.get<AccountApiItem[]>('/platform/app/account/apis');
}

export function fetchTrend(params: TrendQuery) {
  return requestClient.get<TrendPoint[]>('/member/account/trend', {
    params,
  });
}

export function fetchCharges(params: ChargeQuery) {
  return requestClient.get<PageResult<ChargeRecord>>(
    '/member/account/charges',
    {
      params,
    },
  );
}

export function fetchLogs(params: LogQuery) {
  return requestClient.get<PageResult<LogRecord>>('/member/account/logs', {
    params,
  });
}

export function fetchLogDetail(traceId: string) {
  return requestClient.get<LogDetail>(`/member/account/logs/${traceId}`);
}

export function fetchRecentCharges(overrides?: Partial<ChargeQuery>) {
  return fetchCharges({
    pageNo: 1,
    pageSize: 5,
    type: 'DEDUCT',
    ...overrides,
  });
}

export function fetchRecentLogs(overrides?: Partial<LogQuery>) {
  return fetchLogs({
    pageNo: 1,
    pageSize: 5,
    ...overrides,
  });
}
