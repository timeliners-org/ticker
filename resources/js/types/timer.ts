export type TimerStatus = {
    createdAt: string;
    startedAt: string;
    shiftCategoryId: number;
    pausedAt: string | null;
    updateShiftId: number | null;
};

export type ShiftCategory = {
    id: number;
    name: string;
    color: string;
};

export type TodayQuota = {
    id: number;
    date: string;
    quota: number;
};

export type Timer = {
    status: TimerStatus | null;
    categories: ShiftCategory[];
};
