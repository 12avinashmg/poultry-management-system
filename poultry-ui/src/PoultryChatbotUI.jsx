import React, { useState, useEffect } from "react";
import { motion } from "framer-motion";
import {
  Search,
  Home,
  Egg,
  BarChart3,
  Droplet,
  Users,
  Loader2,
  Filter,
  ChevronRight
} from "lucide-react";

const API = "http://localhost/POULTRY/chatbot.php";

const options = [
  { id: "mortality", title: "Mortality", subtitle: "Bird deaths & analysis", icon: Droplet },
  { id: "production", title: "Production", subtitle: "Egg production stats", icon: Egg },
  { id: "sales", title: "Sales & Revenue", subtitle: "Sales performance", icon: BarChart3 },
  { id: "feed", title: "Feed", subtitle: "Consumption & cost", icon: Home },
  { id: "employee", title: "Employees", subtitle: "Salaries & roles", icon: Users }
];

export default function PoultryChatbotUI() {
  const [selected, setSelected] = useState(null);
  const [loading, setLoading] = useState(false);
  const [reply, setReply] = useState("");
  const [data, setData] = useState(null);
  const [search, setSearch] = useState("");

  async function sendRequest(message) {
    setLoading(true);
    setData(null);

    const res = await fetch(API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message })
    });

    const json = await res.json();
    setLoading(false);
    setReply(json.reply_text);
    setData(json.response);
  }

  useEffect(() => {
    // Initial menu show
  }, []);

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-blue-100 p-6">
      <div className="max-w-7xl mx-auto flex gap-6">

        {/* Sidebar */}
        <motion.aside
          initial={{ x: -20, opacity: 0 }}
          animate={{ x: 0, opacity: 1 }}
          className="w-72 bg-white/80 shadow-xl backdrop-blur-lg rounded-3xl p-6 h-[90vh] sticky top-6"
        >
          <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
            <Filter size={20} className="text-indigo-600" /> Reports
          </h2>

          {options.map((o) => {
            const Icon = o.icon;
            const active = selected === o.id;

            return (
              <motion.button
                whileHover={{ scale: 1.02 }}
                key={o.id}
                onClick={() => {
                  setSelected(o.id);
                  sendRequest(o.id);
                }}
                className={`w-full flex items-center justify-between p-4 rounded-2xl mb-3 transition shadow-sm
                ${active ? "bg-indigo-600 text-white shadow-lg" : "bg-gray-100 text-gray-700"}`}
              >
                <div className="flex items-center gap-3">
                  <Icon size={22} />
                  <div className="text-left">
                    <div className="font-semibold">{o.title}</div>
                    <div className="text-xs opacity-70">{o.subtitle}</div>
                  </div>
                </div>
                <ChevronRight />
              </motion.button>
            );
          })}
        </motion.aside>

        {/* Content Area */}
        <motion.div
          initial={{ y: 20, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          className="flex-1"
        >
          <div className="bg-white/70 backdrop-blur-xl shadow-xl rounded-3xl p-8 min-h-[90vh]">
            <div className="flex justify-between items-center mb-6">
              <div>
                <h1 className="text-3xl font-bold text-indigo-700">
                  Poultry Admin Dashboard
                </h1>
                <p className="text-sm text-gray-500">{reply}</p>
              </div>

              <div className="relative">
                <Search className="absolute left-3 top-2 text-gray-400" size={18} />
                <input
                  className="pl-10 pr-3 py-2 rounded-xl border bg-gray-50"
                  placeholder="Search rows…"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>

            {/* Loading */}
            {loading && (
              <div className="flex justify-center py-20">
                <Loader2 size={40} className="animate-spin text-indigo-600" />
              </div>
            )}

            {/* Table Result */}
            {!loading && data?.type === "table" && (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="p-4 bg-white rounded-2xl shadow-lg"
              >
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-gray-600">
                      {data.table.columns.map((c) => (
                        <th key={c} className="p-3 text-left">{c}</th>
                      ))}
                    </tr>
                  </thead>

                  <tbody>
                    {data.table.rows
                      .filter((row) =>
                        row.join(" ").toLowerCase().includes(search.toLowerCase())
                      )
                      .map((row, i) => (
                        <tr key={i} className="border-b hover:bg-gray-100">
                          {row.map((cell, j) => (
                            <td key={j} className="p-3">{cell}</td>
                          ))}
                        </tr>
                      ))}
                  </tbody>
                </table>
              </motion.div>
            )}

            {/* Summary / other types */}
            {!loading && data?.type === "summary" && (
              <div className="p-6 bg-indigo-100 rounded-3xl shadow-lg text-xl">
                {reply}
              </div>
            )}
          </div>
        </motion.div>
      </div>
    </div>
  );
}
