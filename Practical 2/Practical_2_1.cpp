#include<iostream>
using namespace std;

 int recursive(int numP[10], int t, int no){
  if(no < 0){
    return -1;
  }
  if(numP[no]==t){
    return numP[no];
  }
   return recursive(numP,t,no-1);
  
}

int main(){
int numPlate[10]={1212, 2002, 4560, 6789, 5679, 4222, 7870, 2354, 6568, 3215};
int target=6789;
int n=9;
cout << "Itterative approach: " << endl;
for(int i=0; i<=9; i++){
    if(numPlate[i]==target){
     cout << "The number plate that we are finding is : " << numPlate[i] << endl;
      break;
    }
}
cout << "--------------------------------------------------------------------------" << endl;
cout << "Recursive Approach : " << endl; 
cout << "The number plate that we are finding is : " << recursive(numPlate,target,n);
    return 0;
}