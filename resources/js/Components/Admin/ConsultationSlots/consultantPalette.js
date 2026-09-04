// Consultant colours for the week grid and its ownership buttons (011 US33).
//
// The class strings are spelled out rather than composed, and they live in the
// front end rather than coming down with the payload, for one reason: Tailwind
// v4 generates CSS by scanning source files. A class assembled at runtime —
// `bg-${colour}-100` — or handed over by the server is never seen by that scan,
// so it works in dev (where the full stylesheet is available) and vanishes in
// production. That failure is silent and looks like a data problem (FR-176).
//
// The server sends only a `color_index`: the consultant's position in the
// roster ordered by user id, so renaming somebody never reshuffles the colours
// (D126).

/**
 * Eight hues, each in three roles:
 *  - `solid`  the selected ownership button
 *  - `soft`   an unselected button (tint + matching border)
 *  - `cell`   an open slot in the grid
 *  - `drag`   the drag preview while releasing time for this consultant
 */
export const CONSULTANT_COLORS = [
  {
    solid: 'bg-sky-600 text-white border-sky-600 ring-sky-300',
    soft: 'bg-sky-50 text-sky-800 border-sky-300',
    cell: 'bg-sky-100 hover:bg-sky-200 cursor-pointer',
    drag: '!bg-sky-400/60',
  },
  {
    solid: 'bg-emerald-600 text-white border-emerald-600 ring-emerald-300',
    soft: 'bg-emerald-50 text-emerald-800 border-emerald-300',
    cell: 'bg-emerald-100 hover:bg-emerald-200 cursor-pointer',
    drag: '!bg-emerald-400/60',
  },
  {
    solid: 'bg-violet-600 text-white border-violet-600 ring-violet-300',
    soft: 'bg-violet-50 text-violet-800 border-violet-300',
    cell: 'bg-violet-100 hover:bg-violet-200 cursor-pointer',
    drag: '!bg-violet-400/60',
  },
  {
    solid: 'bg-pink-600 text-white border-pink-600 ring-pink-300',
    soft: 'bg-pink-50 text-pink-800 border-pink-300',
    cell: 'bg-pink-100 hover:bg-pink-200 cursor-pointer',
    drag: '!bg-pink-400/60',
  },
  {
    solid: 'bg-orange-600 text-white border-orange-600 ring-orange-300',
    soft: 'bg-orange-50 text-orange-800 border-orange-300',
    cell: 'bg-orange-100 hover:bg-orange-200 cursor-pointer',
    drag: '!bg-orange-400/60',
  },
  {
    solid: 'bg-cyan-600 text-white border-cyan-600 ring-cyan-300',
    soft: 'bg-cyan-50 text-cyan-800 border-cyan-300',
    cell: 'bg-cyan-100 hover:bg-cyan-200 cursor-pointer',
    drag: '!bg-cyan-400/60',
  },
  {
    solid: 'bg-lime-600 text-white border-lime-600 ring-lime-300',
    soft: 'bg-lime-50 text-lime-800 border-lime-300',
    cell: 'bg-lime-100 hover:bg-lime-200 cursor-pointer',
    drag: '!bg-lime-400/60',
  },
  {
    solid: 'bg-rose-600 text-white border-rose-600 ring-rose-300',
    soft: 'bg-rose-50 text-rose-800 border-rose-300',
    cell: 'bg-rose-100 hover:bg-rose-200 cursor-pointer',
    drag: '!bg-rose-400/60',
  },
]

/** Slots nobody owns — legal (FR-062) and mostly pre-dating the feature. */
export const UNASSIGNED_COLOR = {
  solid: 'bg-gray-500 text-white border-gray-500 ring-gray-300',
  soft: 'bg-gray-50 text-gray-600 border-gray-300',
  cell: 'bg-gray-200 hover:bg-gray-300 cursor-pointer',
  drag: '!bg-gray-400/60',
}

/**
 * Colours for a roster position. Wraps past the end of the palette — two
 * consultants sharing a hue is survivable (the button text and the tooltip
 * still name them); an uncoloured one is not.
 */
export function colorFor(index) {
  if (index === null || index === undefined) return UNASSIGNED_COLOR

  return CONSULTANT_COLORS[index % CONSULTANT_COLORS.length]
}
