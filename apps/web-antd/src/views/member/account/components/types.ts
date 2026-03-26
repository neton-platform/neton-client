export type RangeType = '1d' | '7d' | '30d' | 'custom';

export interface RangeValue {
  range: RangeType;
  startTime?: string;
  endTime?: string;
}
