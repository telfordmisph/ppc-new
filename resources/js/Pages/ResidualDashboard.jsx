import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, usePage } from "@inertiajs/react";

const ResidualDashboard = () => {
  return (
      <AuthenticatedLayout>
          <Head title="Residual Dashboard" />
          <h1 className="text-2xl font-bold">Residual Dashboard</h1>
      </AuthenticatedLayout>
  );
}

export default ResidualDashboard