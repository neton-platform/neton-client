<script lang="ts" setup>
import type { RangeType, RangeValue } from './types';

import type { Dayjs } from 'dayjs';

import { ref, watch } from 'vue';

import { DatePicker, RadioButton, RadioGroup, Space, message } from 'ant-design-vue';
import dayjs from 'dayjs';

const RANGE_LIMIT_DAYS = 90;
const DATE_TIME_FORMAT = 'YYYY-MM-DD HH:mm:ss';

const props = defineProps<{
  value: RangeValue;
}>();

const emits = defineEmits<{
  change: [value: RangeValue];
}>();

const selectedRange = ref<RangeType>(props.value?.range ?? '7d');
const customValue = ref<[Dayjs, Dayjs]>();

watch(
  () => props.value,
  (val) => {
    if (!val) return;
    selectedRange.value = val.range;
    if (val.range === 'custom' && val.startTime && val.endTime) {
      customValue.value = [dayjs(val.startTime), dayjs(val.endTime)];
    } else {
      customValue.value = undefined;
    }
  },
  { immediate: true },
);

function emitChange(value: RangeValue) {
  emits('change', value);
}

function handleQuick(range: RangeType) {
  selectedRange.value = range;
  customValue.value = undefined;
  emitChange({ range });
}

function handleCustomChange(values: null | [Dayjs, Dayjs]) {
  if (!values || values.length < 2) {
    message.warning('请选择起止时间');
    return;
  }
  const [start, end] = values;
  const diff = end.endOf('day').diff(start.startOf('day'), 'day');
  if (diff > RANGE_LIMIT_DAYS) {
    message.warning(`自定义区间最长 ${RANGE_LIMIT_DAYS} 天`);
    return;
  }
  selectedRange.value = 'custom';
  emitChange({
    range: 'custom',
    startTime: start.startOf('day').format(DATE_TIME_FORMAT),
    endTime: end.endOf('day').format(DATE_TIME_FORMAT),
  });
}
</script>

<template>
  <Space wrap>
    <RadioGroup v-model:value="selectedRange">
      <RadioButton value="1d" @click="handleQuick('1d')">最近1天</RadioButton>
      <RadioButton value="7d" @click="handleQuick('7d')">最近7天</RadioButton>
      <RadioButton value="30d" @click="handleQuick('30d')">最近30天</RadioButton>
      <RadioButton value="custom">自定义</RadioButton>
    </RadioGroup>
    <DatePicker.RangePicker
      v-model:value="customValue"
      show-time
      format="YYYY-MM-DD HH:mm"
      value-format="YYYY-MM-DD HH:mm:ss"
      style="min-width: 280px"
      :placeholder="['开始时间', '结束时间']"
      @ok="handleCustomChange(customValue)"
      @change="handleCustomChange"
    />
  </Space>
</template>
