export type DashboardShift = {
    id: number;
    shift_category_id: number;
    category_name: string;
    category_color: string;
    is_break: boolean;
    start_time: string;
    duration_seconds: number;
};

export type DashboardShifts = Record<string, DashboardShift[]>;
