#include <iostream>
#include <algorithm>
#include <vector>

using namespace std;
typedef long long ll;

int main(){
    int t; cin >>t;
    while(t--){
        int m; cin>>m;
        vector<ll> a(m);
        for(int i=0; i<m; i++) cin>>a[i];

        

        vector<ll> sort_a = a;
        sort(sort_a.begin(), sort_a.end());

        if(a  == sort_a){
            cout<<"YES\n";
            for(int i=0;i<m;i++) cout<<a[i]<<" ";
            cout<<"\n";
        } else {
            cout<<"NO\n";
        }

    }
}