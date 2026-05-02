import { Navigate, Route, Routes } from 'react-router-dom';
import { ProtectedRoute } from './auth/ProtectedRoute';
import { AppLayout } from './layout/AppLayout';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { DashboardPage } from './pages/DashboardPage';
import { PlanningPage } from './pages/PlanningPage';
import { HistoryPage } from './pages/HistoryPage';
import { WorkoutNewPage } from './pages/WorkoutNewPage';
import { WorkoutDetailsPage } from './pages/WorkoutDetailsPage';
import { AchievementsPage } from './pages/AchievementsPage';

export function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route path="/" element={<DashboardPage />} />
          <Route path="/planning" element={<PlanningPage />} />
          <Route path="/history" element={<HistoryPage />} />
          <Route path="/workouts/new" element={<WorkoutNewPage />} />
          <Route path="/workouts/:id" element={<WorkoutDetailsPage />} />
          <Route path="/achievements" element={<AchievementsPage />} />
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
