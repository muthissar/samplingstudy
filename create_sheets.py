import argparse
import json
from pathlib import Path
import subprocess
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    # in_folder = '/mnt/hel.cp.jku.at/devel/python3/DSTM/out/session_seq/gen'
    parser.add_argument('--ckpt', type=str)
    parser.add_argument('--method', type=str, default='typical', choices=['typical', 'nucleus', 'greedy', 'ic_curve'])
    parser.add_argument('--no_remi', action='store_true', default=False, help="Use REMI encoding instead of midi-like." )
    parser.add_argument('--tau', type=float, default=-1.0)
    parser.add_argument('--crop', action='store_true', default=False, help="Use REMI encoding instead of midi-like." )
    args = parser.parse_args()
    ckpt = (Path(args.ckpt).stem).split('--')[0]
    base = '/mnt/hel.cp.jku.at/devel/python3/DSTM'
    dir = f'{base}/out/session_seq/gen/{ckpt}/{args.method}{"_remi" if not args.no_remi else ""}_{"_crop" if args.crop else ""}'
    if args.method in ['nucleus', 'typical']:
        dir += f'/{args.tau}/'
    print(f'in {dir}')
    out_folder = f'{base}/out/session_seq/sheets'
    # sampling_methods = ['greedy_remi', 'nucleus_remi', 'typical_remi']
    # methods = [Path(f"{in_folder}/{method}") for method in sampling_methods]
    # methods.append('/mnt/hel.cp.jku.at/devel/python3/DSTM/out/session_seq/gen/orig')
    dirs = set([])
    mscore_conf = []
    # for method in methods:
    for p in Path(dir).rglob('*.mid'):
        p_index = p.parents._parts.index('gen')
        exp = p.parents._parts[p_index+1:-1]
        out_folder_l = Path(out_folder, *exp)
        dirs.add(str(out_folder_l))
        out_file = out_folder_l.joinpath(f"{p.stem}.svg")
        if out_file.exists():
            continue
        mscore_conf.append({
            'in': str(p),
            'out': str(out_file),
        })
    for dir in dirs:
        print(f'in {dir}')
        Path(dir).mkdir(parents=True, exist_ok=True)
    with open('mscore_conf.json', 'w') as f:
        json.dump(mscore_conf, f)
    subprocess.run(['mscore', '-j', 'mscore_conf.json'])
    Path(mscore_conf).unlink()


# Print everything
# change color of buttons
# have borders for the experiment
# only more extreme
